<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\TrainingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $region = $request->query('region');
            $complaintsOnly = $request->boolean('complaints_only');
            $search = trim((string) $request->query('instructors_q'));

            $instructors = Instructor::when($region, fn ($query) => $query->where('region', $region))
                ->when($complaintsOnly, fn ($query) => $query->whereNotNull('complaints')->where('complaints', '!=', ''))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('training_type', 'like', "%{$search}%")
                            ->orWhere('certificate_code', 'like', "%{$search}%")
                            ->orWhere('agency_organization', 'like', "%{$search}%")
                            ->orWhere('lgu', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->get();

            $instructorsByRegion = $instructors
                ->groupBy(fn (Instructor $instructor) => $instructor->region ?: 'Central / Unassigned')
                ->sortKeys();

            // The roster search box re-requests this same route and swaps in just the
            // results, so typing doesn't reload the whole page — see resources/views/
            // admin/partials/live-search-script.blade.php for the client-side half.
            if ($request->ajax() && $request->query('_section') === 'instructor-roster') {
                return view('admin.partials.instructor-roster-results', [
                    'instructorsByRegion' => $instructorsByRegion,
                    'complaintsOnly' => $complaintsOnly,
                    'selectedRegion' => $region,
                    'instructorSearch' => $search,
                ]);
            }

            $withComplaints = $instructors->filter(fn (Instructor $instructor) => filled($instructor->complaints));

            return view('admin.super-admin.instructors.index', [
                'instructorsByRegion' => $instructorsByRegion,
                'regions' => config('regions.list'),
                'selectedRegion' => $region,
                'complaintsOnly' => $complaintsOnly,
                'instructorSearch' => $search,
                'stats' => [
                    'total' => $instructors->pluck('name')->unique()->count(),
                    'regions' => $instructors->pluck('region')->filter()->unique()->count(),
                    'complaints' => $withComplaints->pluck('name')->unique()->count(),
                ],
            ]);
        }

        $instructors = Instructor::where('region', $user->region)->orderBy('name')->paginate(20);

        return view('admin.instructors', [
            'instructors' => $instructors,
            'regions' => config('regions.list'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'sex' => ['nullable', 'string', 'in:Male,Female,Other'],
            'position' => ['nullable', 'string', 'max:255'],
            'training_type' => ['required', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'certification' => ['nullable', 'string', 'max:255'],
            'certificate_code' => ['nullable', 'string', 'max:255'],
            'deployment' => ['nullable', 'string', 'max:255'],
            'deployment_date' => ['nullable', 'date'],
            'deployment_role' => ['nullable', 'string', 'max:255'],
            'agency_organization' => ['nullable', 'string', 'max:255'],
            'lgu' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'in:'.implode(',', config('regions.list'))],
        ]);

        // Regional admins can only add instructors under their own region.
        if ($request->user()->isAdmin()) {
            $validated['region'] = $request->user()->region;
        }

        Instructor::create($validated);

        return Redirect::back()->with('status', "{$validated['name']} was added to the instructor roster.");
    }

    /**
     * Correct a roster row's deployment record — the only fields an
     * instructor row can be edited on after creation.
     */
    public function update(Request $request, Instructor $instructor): RedirectResponse
    {
        abort_if($request->user()->isAdmin() && $instructor->region !== $request->user()->region, 403);

        $validated = $request->validate([
            'deployment' => ['nullable', 'string', 'max:255'],
            'deployment_date' => ['nullable', 'date'],
            'deployment_role' => ['nullable', 'string', 'max:255'],
            'agency_organization' => ['nullable', 'string', 'max:255'],
            'lgu' => ['nullable', 'string', 'max:255'],
        ]);

        $instructor->update($validated);

        return Redirect::back()->with('status', "Deployment details updated for {$instructor->name}.");
    }

    public function show(Instructor $instructor): View
    {
        $records = Instructor::where('name', $instructor->name)->orderBy('training_type')->get();

        $ratings = $records->pluck('rating')->filter(fn ($r) => is_numeric($r));

        return view('admin.super-admin.instructors.show', [
            'instructor' => $instructor,
            'records' => $records,
            'overallRating' => $ratings->isNotEmpty() ? round($ratings->avg(), 2) : null,
            'sessions' => $this->sessionHistory($instructor),
        ]);
    }

    /**
     * One row per completed TrainingRequest this instructor is formally
     * attached to (via Summary's instructor selection, not just sharing a
     * name on the roster) — Category, Module(s), Rate, and Comments per
     * session. Modules are the session's own recorded module list, not
     * verified as taught specifically by this instructor, since nothing in
     * the schema links a module to a particular co-instructor.
     */
    private function sessionHistory(Instructor $instructor): Collection
    {
        return $instructor->trainingRequests()
            ->where('status', TrainingRequest::STATUS_COMPLETED)
            ->with(['participantEvaluations', 'trainingEvaluation'])
            ->orderByDesc('preferred_date')
            ->get()
            ->map(function (TrainingRequest $trainingRequest) use ($instructor) {
                $ratingRows = $trainingRequest->participantEvaluations
                    ->pluck('instructor_ratings')->filter()->flatten(1)
                    ->where('instructor_id', $instructor->id);

                $scores = $ratingRows->pluck('rating')->filter(fn ($r) => is_numeric($r));

                return [
                    'training_title' => $trainingRequest->training_title,
                    'category' => $trainingRequest->categoryLabel(),
                    'preferred_date' => $trainingRequest->preferred_date,
                    'venue' => $trainingRequest->venue,
                    'modules' => collect($trainingRequest->trainingEvaluation?->module_ratings ?? [])
                        ->pluck('module')->filter()->unique()->values()->all(),
                    'rate' => $scores->isNotEmpty() ? round($scores->avg(), 2) : null,
                    'responses' => $scores->count(),
                    'comments' => $ratingRows->pluck('comment')->filter(fn ($c) => filled(trim((string) $c)))->values()->all(),
                ];
            });
    }
}
