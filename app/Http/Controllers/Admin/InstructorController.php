<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\InstructorCertification;
use App\Models\InstructorDeployment;
use App\Models\InstructorTrainingType;
use App\Models\TrainingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The instructor roster.
 *
 * A Regional Admin maintains their own region's instructors — adding them and
 * recording their courses, certifications, and deployments — while a Super
 * Admin does the same for every region and is the only one who can move an
 * instructor between regions. Each of those three things is many-per-person
 * (see the Instructor model), so they're added and removed one row at a time
 * rather than edited as single fields.
 */
class InstructorController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $region = $request->query('region');
            $complaintsOnly = $request->boolean('complaints_only');
            $search = trim((string) $request->query('instructors_q'));

            $instructors = Instructor::with(['trainingTypes', 'certifications', 'deployments'])
                ->when($region, fn ($query) => $query->where('region', $region))
                ->when($complaintsOnly, fn ($query) => $query->whereNotNull('complaints')->where('complaints', '!=', ''))
                ->tap(fn ($query) => $this->applySearch($query, $search))
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
                    'total' => $instructors->count(),
                    'regions' => $instructors->pluck('region')->filter()->unique()->count(),
                    'complaints' => $withComplaints->count(),
                ],
            ]);
        }

        $trainingType = trim((string) $request->query('training_type'));
        $search = trim((string) $request->query('instructors_q'));

        $instructors = Instructor::with(['trainingTypes', 'certifications', 'deployments'])
            ->where('region', $user->region)
            ->when($trainingType !== '', fn ($query) => $query->whereHas(
                'trainingTypes',
                fn ($t) => $t->where('training_type', $trainingType)
            ))
            ->tap(fn ($query) => $this->applySearch($query, $search))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $trainingTypes = InstructorTrainingType::whereHas(
            'instructor',
            fn ($q) => $q->where('region', $user->region)
        )
            ->distinct()
            ->orderBy('training_type')
            ->pluck('training_type');

        // The roster search box re-requests this same route and swaps in just the
        // results, so typing doesn't reload the whole page — see resources/views/
        // admin/partials/live-search-script.blade.php for the client-side half.
        if ($request->ajax() && $request->query('_section') === 'instructor-roster') {
            return view('admin.partials.instructor-roster', [
                'instructors' => $instructors,
            ]);
        }

        return view('admin.instructors', [
            'instructors' => $instructors,
            'regions' => config('regions.list'),
            'trainingTypes' => $trainingTypes,
            'selectedTrainingType' => $trainingType,
            'instructorSearch' => $search,
        ]);
    }

    /**
     * Roster search spans the person and their child rows — a name, the
     * courses they teach, a certificate code, or their agency/LGU.
     */
    private function applySearch($query, string $search): void
    {
        $query->when($search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('agency_organization', 'like', "%{$search}%")
                    ->orWhere('lgu', 'like', "%{$search}%")
                    ->orWhereHas('trainingTypes', fn ($t) => $t->where('training_type', 'like', "%{$search}%")
                        ->orWhere('certificate_code', 'like', "%{$search}%"))
                    ->orWhereHas('certifications', fn ($c) => $c->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            });
        });
    }

    /**
     * Adds an instructor along with however many courses, certifications, and
     * deployments were filled in on the form — all three are repeatable rows,
     * so a person who teaches four courses is entered once, not four times.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'sex' => ['nullable', 'string', 'in:Male,Female,Other'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'position' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'agency_organization' => ['nullable', 'string', 'max:255'],
            'lgu' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'in:'.implode(',', config('regions.list'))],

            'training_types' => ['required', 'array', 'min:1'],
            'training_types.*.training_type' => ['nullable', 'string', 'max:255'],
            'training_types.*.certificate_code' => ['nullable', 'string', 'max:255'],

            'certifications' => ['nullable', 'array'],
            'certifications.*.title' => ['nullable', 'string', 'max:255'],
            'certifications.*.code' => ['nullable', 'string', 'max:255'],
            'certifications.*.issued_on' => ['nullable', 'date'],
            'certifications.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            'deployments' => ['nullable', 'array'],
            'deployments.*.deployment' => ['nullable', 'string', 'max:255'],
            'deployments.*.deployment_date' => ['nullable', 'date'],
            'deployments.*.deployment_role' => ['nullable', 'string', 'max:255'],
            'deployments.*.area' => ['nullable', 'string', 'max:255'],
            'deployments.*.city' => ['nullable', 'string', 'max:255'],
        ]);

        // At least one actual course. The form always submits its first row
        // even when left blank, so array|min:1 alone wouldn't catch an empty one.
        $courses = $this->filledRows($validated['training_types'] ?? [], 'training_type');

        if ($courses->isEmpty()) {
            return Redirect::back()
                ->withErrors(['training_types' => __('Add at least one type of training this instructor can teach.')])
                ->withInput();
        }

        // Regional admins can only add instructors under their own region.
        if ($request->user()->isAdmin()) {
            $validated['region'] = $request->user()->region;
        }

        $instructor = Instructor::create([
            ...collect($validated)->only([
                'name', 'email', 'phone', 'sex', 'position',
                'specialization', 'agency_organization', 'lgu', 'region',
            ])->all(),
            'photo_path' => $request->hasFile('photo')
                ? $request->file('photo')->store('instructors/photos', 'public')
                : null,
        ]);

        $instructor->trainingTypes()->createMany($courses->values()->all());

        // Keyed by the original row index so each certification keeps its own
        // uploaded file, which validation strips out of $validated.
        foreach ($this->filledRows($validated['certifications'] ?? [], 'title') as $index => $row) {
            $instructor->certifications()->create([
                ...$row,
                'file_path' => $request->file("certifications.{$index}.file")?->store('instructors/certificates', 'public'),
            ]);
        }

        $instructor->deployments()->createMany(
            $this->filledRows($validated['deployments'] ?? [], 'deployment')->values()->all()
        );

        return Redirect::back()->with('status', "{$instructor->name} was added to the instructor roster.");
    }

    public function addTrainingType(Request $request, Instructor $instructor): RedirectResponse
    {
        $this->authorizeFor($request, $instructor);

        $validated = $request->validate([
            'training_type' => ['required', 'string', 'max:255'],
            'certificate_code' => ['nullable', 'string', 'max:255'],
        ]);

        $instructor->trainingTypes()->create($validated);

        return Redirect::back()->with('status', "{$validated['training_type']} added to {$instructor->name}'s courses.");
    }

    public function removeTrainingType(Request $request, InstructorTrainingType $trainingType): RedirectResponse
    {
        $trainingType->loadMissing('instructor');
        $this->authorizeFor($request, $trainingType->instructor);

        // An instructor with no courses at all drops off every roster filter
        // and can't be attached to a session.
        if ($trainingType->instructor->trainingTypes()->count() <= 1) {
            return Redirect::back()->withErrors([
                'training_type' => __('An instructor needs at least one type of training — add the replacement before removing this one.'),
            ]);
        }

        $instructor = $trainingType->instructor;
        $trainingType->delete();
        $instructor->refreshOverallRating();

        return Redirect::back()->with('status', 'Training type removed.');
    }

    public function addCertification(Request $request, Instructor $instructor): RedirectResponse
    {
        $this->authorizeFor($request, $instructor);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'issued_on' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $instructor->certifications()->create([
            ...collect($validated)->except('file')->all(),
            'file_path' => $request->file('file')?->store('instructors/certificates', 'public'),
        ]);

        return Redirect::back()->with('status', "Certification added for {$instructor->name}.");
    }

    public function removeCertification(Request $request, InstructorCertification $certification): RedirectResponse
    {
        $certification->loadMissing('instructor');
        $this->authorizeFor($request, $certification->instructor);

        if ($certification->file_path) {
            Storage::disk('public')->delete($certification->file_path);
        }

        $certification->delete();

        return Redirect::back()->with('status', 'Certification removed.');
    }

    public function addDeployment(Request $request, Instructor $instructor): RedirectResponse
    {
        $this->authorizeFor($request, $instructor);

        $validated = $request->validate($this->deploymentRules());

        $instructor->deployments()->create($validated);

        return Redirect::back()->with('status', "Deployment recorded for {$instructor->name}.");
    }

    /**
     * Correct one deployment record — details of where someone was sent, not
     * anything about the person.
     */
    public function updateDeployment(Request $request, InstructorDeployment $deployment): RedirectResponse
    {
        $deployment->loadMissing('instructor');
        $this->authorizeFor($request, $deployment->instructor);

        $deployment->update($request->validate($this->deploymentRules()));

        return Redirect::back()->with('status', "Deployment updated for {$deployment->instructor->name}.");
    }

    public function removeDeployment(Request $request, InstructorDeployment $deployment): RedirectResponse
    {
        $deployment->loadMissing('instructor');
        $this->authorizeFor($request, $deployment->instructor);

        $deployment->delete();

        return Redirect::back()->with('status', 'Deployment record removed.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function deploymentRules(): array
    {
        return [
            'deployment' => ['required', 'string', 'max:255'],
            'deployment_date' => ['nullable', 'date'],
            'deployment_role' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Reassign an instructor to a different region. Instructors get posted to
     * another regional office, or sent to run a training outside their own,
     * and the roster is grouped and filtered by region everywhere — so this
     * is what moves them onto the receiving region's roster.
     *
     * Super Admin only, by route — a Regional Admin can't post an instructor
     * out of, or into, their own region.
     */
    public function updateRegion(Request $request, Instructor $instructor): RedirectResponse
    {
        $validated = $request->validate([
            'region' => ['required', 'string', 'in:'.implode(',', config('regions.list'))],
        ]);

        $previous = $instructor->region;

        if ($previous === $validated['region']) {
            return Redirect::back();
        }

        $instructor->update(['region' => $validated['region']]);

        $from = $previous ?: 'Central / Unassigned';

        return Redirect::back()->with('status', "{$instructor->name} was moved from {$from} to {$validated['region']}.");
    }

    /**
     * Upload (or replace) an instructor's profile photo.
     */
    public function uploadPhoto(Request $request, Instructor $instructor): RedirectResponse
    {
        $this->authorizeFor($request, $instructor);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        $previousPhoto = $instructor->photo_path;

        $instructor->update([
            'photo_path' => $validated['photo']->store('instructors/photos', 'public'),
        ]);

        // Delete the photo this upload replaced so unused files don't pile up.
        if ($previousPhoto) {
            Storage::disk('public')->delete($previousPhoto);
        }

        return Redirect::back()->with('status', "Photo updated for {$instructor->name}.");
    }

    public function show(Request $request, Instructor $instructor): View
    {
        $this->authorizeFor($request, $instructor, read: true);

        $instructor->load(['trainingTypes', 'certifications', 'deployments']);

        $ratings = $instructor->trainingTypes->pluck('rating')->filter(fn ($r) => is_numeric($r));

        return view('admin.super-admin.instructors.show', [
            'instructor' => $instructor,
            'records' => $instructor->trainingTypes,
            'regions' => config('regions.list'),
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

    /**
     * Drops repeatable-row entries the admin added and left blank, keyed on
     * the one field that makes the row mean anything. Keeps the original row
     * indexes so an uploaded file can still be matched back to its row.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function filledRows(array $rows, string $requiredKey): Collection
    {
        return collect($rows)
            ->filter(fn ($row) => filled(trim((string) ($row[$requiredKey] ?? ''))))
            ->map(fn ($row) => collect($row)->except('file')->all());
    }

    /**
     * A Regional Admin works only on their own region's instructors; a Super
     * Admin on anyone's. Reads 404 and writes 403, matching how the rest of
     * the app treats "not yours" versus "not allowed".
     */
    private function authorizeFor(Request $request, ?Instructor $instructor, bool $read = false): void
    {
        abort_unless($instructor, 404);

        $actor = $request->user();
        abort_if($actor->isAdmin() && $instructor->region !== $actor->region, $read ? 404 : 403);
    }
}
