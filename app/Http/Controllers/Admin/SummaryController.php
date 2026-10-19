<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\ParticipantEvaluation;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\EvaluationSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SummaryController extends Controller
{
    // Same colors used on the Calendar / Tools status chart, so a status means
    // the same thing everywhere an admin sees it.
    const STATUS_COLORS = [
        TrainingRequest::STATUS_SUBMITTED => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600',
        TrainingRequest::STATUS_UNDER_REVIEW => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700',
        TrainingRequest::STATUS_APPROVED => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700',
        TrainingRequest::STATUS_DECLINED => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700',
        TrainingRequest::STATUS_COMPLETED => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q'));
        $region = $user->isSuperAdmin() ? $request->query('region') : null;

        // Listed whatever their status: a Regional Admin's scheduled training is
        // approved the moment it is saved, so there is nothing to filter for.
        //
        // Super Admin only: they schedule trainings and are the ones who move a
        // training to Completed, which is what issues the participants'
        // certificates. A Regional Admin's Summary page is the Evaluations
        // section alone, so this query is skipped for them entirely.
        $records = ! $user->isSuperAdmin() ? null : TrainingRequest::with(['user', 'participants'])
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('venue', 'like', "%{$search}%")
                        ->orWhere('training_title', 'like', "%{$search}%")
                        ->orWhere('requesting_agency', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhereRaw("DATE_FORMAT(preferred_date, '%b %d, %Y') like ?", ["%{$search}%"])
                        ->orWhereHas('participants', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('preferred_date')
            ->paginate(10)
            ->withQueryString()
            ->fragment('training-requests');

        $evaluationSearch = trim((string) $request->query('evaluations_q'));

        // Evaluations roll up per training, not per participant: an admin reads
        // how a training was received, and one row per submission said nothing on
        // its own. Only trainings with at least one submission appear; the
        // per-participant breakdown lives on the training's View page.
        $evaluatedTrainings = ($user->isAdmin() || $user->isSuperAdmin())
            ? TrainingRequest::with(['user', 'participants'])
                ->has('participantEvaluations')
                ->withCount('participantEvaluations')
                ->withMax('participantEvaluations', 'updated_at')
                ->when($user->isAdmin(), fn ($query) => $query->where('region', $user->region))
                ->when($region, fn ($query) => $query->where('region', $region))
                ->when($evaluationSearch !== '', function ($query) use ($evaluationSearch) {
                    $query->where(function ($q) use ($evaluationSearch) {
                        $q->where('training_title', 'like', "%{$evaluationSearch}%")
                            ->orWhere('requesting_agency', 'like', "%{$evaluationSearch}%")
                            ->orWhere('venue', 'like', "%{$evaluationSearch}%");
                    });
                })
                ->orderByDesc('participant_evaluations_max_updated_at')
                ->paginate(10, ['*'], 'evaluations')
                ->withQueryString()
                ->fragment('evaluations')
            : null;

        $payload = [
            'records' => $records,
            'search' => $search,
            'evaluatedTrainings' => $evaluatedTrainings,
            'evaluationSearch' => $evaluationSearch,
            'regions' => config('regions.list'),
            'selectedRegion' => $region,
        ];

        // Each search/filter form submits here via fetch() tagged with the
        // section it belongs to, so typing updates just that section's table
        // in place instead of reloading the whole page.
        $sectionPartials = [
            'training-requests' => 'admin.partials.summary-training-requests',
            'evaluations' => 'admin.partials.summary-evaluations',
        ];

        if ($request->ajax() && isset($sectionPartials[$request->query('_section')])) {
            abort_if($request->query('_section') === 'training-requests' && ! $user->isSuperAdmin(), 403);

            return view($sectionPartials[$request->query('_section')], $payload);
        }

        return view('admin.summary', $payload);
    }

    /**
     * One training's evaluation results, read-only — where the Summary page's
     * Evaluations card lands. The Super Admin reads what participants reported
     * and changes nothing here: no status, no roster, no certificate fields.
     * Editing an evaluation is a separate page (EvaluationController), and
     * editing the training itself is edit() below.
     */
    public function evaluations(Request $request, TrainingRequest $trainingRequest, EvaluationSummary $evaluationSummary): View
    {
        // Same region rule as everywhere else a Regional Admin reads a training.
        abort_if($request->user()->isAdmin() && $trainingRequest->region !== $request->user()->region, 403);

        $trainingRequest->load(['trainingEvaluation', 'participantEvaluations.user:id,name', 'instructors'])
            ->loadCount('participants');

        return view('admin.evaluation-summary', [
            'record' => $trainingRequest,
            'session' => $evaluationSummary->forSession($trainingRequest),
        ]);
    }

    public function edit(Request $request, TrainingRequest $trainingRequest): View
    {
        // Regional admins may only manage training requests tagged to their own region.
        abort_if($request->user()->isAdmin() && $trainingRequest->region !== $request->user()->region, 403);

        $trainingRequest->load('participants', 'instructors', 'participantEvaluations.user', 'certificates.user');

        // Regional admins pick from their own roster; Super Admin sees the
        // roster for whatever region this request is tagged to (or everyone,
        // for a request with no region yet).
        $instructorRegion = $request->user()->isAdmin() ? $request->user()->region : $trainingRequest->region;

        $availableInstructors = Instructor::with('trainingTypes')
            ->when($instructorRegion, fn ($query) => $query->where('region', $instructorRegion))
            ->orderBy('name')
            ->get();

        return view('admin.summary-edit', [
            'record' => $trainingRequest,
            'participants' => $trainingRequest->effectiveParticipants(),
            'statusLabels' => TrainingRequest::$statusLabels,
            'certificateRemarksLabels' => TrainingRequest::$certificateRemarksLabels,
            'categoryLabels' => TrainingRequest::$categoryLabels,
            'agencyTypeLabels' => TrainingRequest::$agencyTypeLabels,
            'regions' => config('regions.list'),
            'availableInstructors' => $availableInstructors,
        ]);
    }

    public function update(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        abort_if($request->user()->isAdmin() && $trainingRequest->region !== $request->user()->region, 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(TrainingRequest::$statusLabels))],
            'preferred_date' => ['required', 'date'],
            // The last day of a run, empty for a one-day training. Editable
            // here as well as on the schedule form, or a training that has to
            // move could keep its start and its old end.
            'end_date' => ['nullable', 'date', 'after_or_equal:preferred_date'],
            'venue' => ['required', 'string', 'max:255'],
            'lgu' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'in:'.implode(',', config('regions.list'))],
            // Allows keeping whatever category is already on the record (e.g. a
            // legacy "apb" one) even though it's no longer a selectable option —
            // see admin/summary-edit.blade.php for the matching defensive <option>.
            'category' => ['nullable', 'string', 'in:'.implode(',', array_unique(array_filter([...array_keys(TrainingRequest::$categoryLabels), $trainingRequest->category])))],
            'agency_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(TrainingRequest::$agencyTypeLabels))],
            'teams_organized' => ['nullable', 'integer', 'min:0'],
            'certificate_remarks' => ['nullable', 'string', 'in:'.implode(',', array_keys(TrainingRequest::$certificateRemarksLabels))],
            'instructor_ids' => ['nullable', 'array'],
            'instructor_ids.*' => ['integer', 'exists:instructors,id'],
            // Only Super Admin can staff a request with participants (see the
            // Participants section in summary-edit.blade.php, hidden for regular
            // admins) — regional admins can't file requests themselves, so they
            // don't get a say in who attends one either.
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', [User::ROLE_PARTICIPANT, User::ROLE_ADMIN])),
            ],
        ]);

        // Regional admins can only tag training requests as belonging to their own region.
        if ($request->user()->isAdmin()) {
            $validated['region'] = $request->user()->region;
        }

        $trainingRequest->update(collect($validated)->except(['instructor_ids', 'participant_ids'])->all());
        $trainingRequest->instructors()->sync($validated['instructor_ids'] ?? []);

        if ($request->user()->isSuperAdmin() && array_key_exists('participant_ids', $validated)) {
            $trainingRequest->participants()->sync($validated['participant_ids'] ?? []);
        }

        // Graduate sex/age counts always reflect the actual participant roster
        // for a completed training — never hand-typed, never out of sync.
        // Certificates are generated the same moment — one PDF per
        // participant, each with its own code — and only for participants
        // who don't already have one, so this stays safe to re-save.
        if ($trainingRequest->status === TrainingRequest::STATUS_COMPLETED) {
            $trainingRequest->syncGraduateCountsFromParticipants();
            app(CertificateService::class)->generateForTrainingRequest($trainingRequest);
        }

        return Redirect::route('admin.summary')->with('status', "Changes saved for {$trainingRequest->training_title}.");
    }
}
