<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AtarReport;
use App\Models\Certificate;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Services\AtarReportGenerator;
use App\Services\CertificateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRUD + PDF export for narrative ATAR reports (App\Models\AtarReport) —
 * distinct from AtarRecordController, which only handles the CSV-imported
 * "Training Database" tracker rows.
 *
 * Unusually for this app, the writes here are split the other way round from
 * everywhere else: the Regional Admin is the one who writes the ATAR for
 * their own region, so create()/store() are reachable by them alone (see the
 * role:admin route group), while the Super Admin reads, edits, and deletes
 * every region's. Everything a Regional Admin can reach is scoped to their
 * own region by scopeToViewer()/authorizeView().
 */
class AtarReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = $this->scopeToViewer(AtarReport::with('trainingRequest'), $request)
            ->when(trim((string) $request->query('q')) !== '', fn ($q) => $q->where('title', 'like', '%'.trim($request->query('q')).'%'))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.atar-reports.index', [
            'reports' => $reports,
            'search' => $request->query('q'),
        ]);
    }

    /**
     * Backs the participant-search modal on the Graduates/Dropouts annex
     * tables — same role-scoping as TrainingController::participants(), the
     * existing bulk-training participant picker, extended with a region
     * filter since a common name (there are plenty in a 3,900-participant
     * roster) is much easier to narrow down by region than by scrolling.
     * Either filter works alone; at least one is required so this never
     * dumps the entire roster. Deliberately not narrowed to a Regional
     * Admin's own region: a training draws attendees from neighbouring
     * regions, and the ATAR's graduate annex has to be able to name whoever
     * actually sat in the room.
     */
    public function searchParticipants(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $region = $request->query('region');

        if ($search === '' && ! $region) {
            return response()->json(['data' => []]);
        }

        $users = User::whereIn('role', [User::ROLE_PARTICIPANT, User::ROLE_ADMIN])
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'sex', 'organization', 'agency', 'region']);

        return response()->json(['data' => $users]);
    }

    /**
     * Called once a name is picked from the autocomplete — returns the
     * gender/agency to auto-fill, plus (for the Graduates table only,
     * though harmless to compute either way) a certificate code. Prefers
     * the user's real, already-issued Certificate for this report's linked
     * training if one exists; otherwise synthesizes a code in the exact
     * same ABBREV-REGION-BATCH-YEAR-SEQUENCE format CertificateService
     * issues for real, so a name added here before their real certificate
     * exists still gets a plausible, correctly-formatted placeholder the
     * admin can adjust once the real one is issued.
     */
    public function participantDetails(Request $request, AtarReport $atarReport, User $user, CertificateService $certificates): JsonResponse
    {
        $this->authorizeView($atarReport, $request);

        $trainingRequest = $atarReport->trainingRequest;
        $sequence = count($atarReport->graduates_list ?? []) + 1;

        if ($trainingRequest) {
            $existing = Certificate::where('training_request_id', $trainingRequest->id)
                ->where('user_id', $user->id)
                ->where('type', TrainingRequest::CERTIFICATE_REMARKS_COMPLETION)
                ->first();

            if ($existing) {
                $code = $existing->code;
            } else {
                // Mirrors CertificateService::generateForTrainingRequest()'s
                // own batch-number query exactly, so a synthesized code lines
                // up with what a real certificate for this training would get.
                $batch = TrainingRequest::where('training_slug', $trainingRequest->training_slug)
                    ->where('status', TrainingRequest::STATUS_COMPLETED)
                    ->where('preferred_date', '<', $trainingRequest->preferred_date)
                    ->count() + 1;

                $year = (int) ($trainingRequest->preferred_date?->format('Y') ?? now()->year);
                $code = $certificates->generateCode($trainingRequest->training_title, $trainingRequest->region, $batch, $year, $sequence);
            }
        } else {
            // Written-from-scratch ATAR — no linked training to derive a
            // batch/region from precisely, so this falls back to the
            // report's own title/date and the selected user's own region.
            $year = now()->year;
            if ($atarReport->date_range && preg_match('/(\d{4})/', $atarReport->date_range, $matches)) {
                $year = (int) $matches[1];
            }

            $code = $certificates->generateCode($atarReport->title ?: 'ATAR', $user->region, 1, $year, $sequence);
        }

        return response()->json([
            'name' => $user->name,
            'gender' => $user->sex,
            'agency' => $user->organization ?: $user->agency,
            'code' => $code,
        ]);
    }

    /**
     * Shows the "generate from a training" vs "start blank" choice. Only
     * completed trainings with an actual roster attached are offered —
     * excludes the rare completed request with no participants at all,
     * since Attendees/Graduates would have nothing to draw from either way.
     *
     * Regional-Admin-only (role:admin), so the list is their own region's
     * completed trainings; writing another region's ATAR isn't theirs to do.
     */
    public function create(Request $request): View
    {
        $trainingRequests = $this->creatableTrainings($request->user())
            ->orderByDesc('preferred_date')
            ->get(['id', 'training_title', 'venue', 'preferred_date', 'region']);

        // Arriving from a training's own "Create ATAR" button: that training is
        // preselected. Checked against the same list the dropdown offers, so an
        // id from outside this admin's region simply isn't preselected.
        $selectedTrainingId = (int) $request->query('training_request_id') ?: null;

        return view('admin.atar-reports.create', [
            'trainingRequests' => $trainingRequests,
            'selectedTrainingId' => $trainingRequests->contains('id', $selectedTrainingId) ? $selectedTrainingId : null,
        ]);
    }

    /**
     * Creates the AtarReport for either path chosen on create(): a full
     * data-backed snapshot via AtarReportGenerator, or an empty draft with
     * just the default signatory rows seeded in. Either way, lands the admin
     * on edit() next to fill in (or finish) the narrative sections.
     */
    public function store(Request $request, AtarReportGenerator $generator): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['generate', 'blank'])],
            'training_request_id' => ['required_if:mode,generate', 'nullable', 'exists:training_requests,id'],
        ]);

        // Re-filtered through creatableTrainings() rather than looked up by
        // id alone: the dropdown only ever offers this admin's own region,
        // and a posted id from outside it should 404 rather than be trusted.
        if ($validated['mode'] === 'generate') {
            $trainingRequest = $this->creatableTrainings($request->user())
                ->findOrFail($validated['training_request_id']);
            $data = $generator->generate($trainingRequest);
            $region = $trainingRequest->region;
        } else {
            $data = [
                'status' => AtarReport::STATUS_DRAFT,
                'signatories' => [
                    ['role' => 'Prepared by', 'name' => '', 'title' => ''],
                    ['role' => 'Approved by', 'name' => '', 'title' => ''],
                ],
            ];
            // Nothing to read a region off for a from-scratch report, so it
            // belongs to whoever wrote it.
            $region = $request->user()->region;
        }

        $data['created_by'] = $request->user()->id;
        $data['region'] = $region;

        $report = AtarReport::create($data);

        return redirect()->route('admin.atar-reports.edit', $report)
            ->with('status', $validated['mode'] === 'generate'
                ? 'ATAR generated from training data — review and complete the narrative sections.'
                : 'Blank ATAR created — fill in the details below.');
    }

    public function edit(Request $request, AtarReport $atarReport): View
    {
        $this->authorizeView($atarReport, $request);

        $atarReport->load('trainingRequest');

        return view('admin.atar-reports.edit', [
            'report' => $atarReport,
            'regions' => config('regions.list'),
        ]);
    }

    /**
     * Saves every section of the form: the plain header/narrative fields,
     * the repeatable-row tables (graduates/dropouts/lecturers/signatories/L1
     * modules — each submitted as a JSON-ish nested array via Alpine's
     * `name="section[index][field]"` inputs), the fixed L2 stats grid, and
     * any newly-uploaded or removed photos.
     */
    public function update(Request $request, AtarReport $atarReport): RedirectResponse
    {
        $this->authorizeView($atarReport, $request);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'date_range' => ['nullable', 'string', 'max:255'],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'background' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'attendees_narrative' => ['nullable', 'string'],
            'highlights' => ['nullable', 'string'],
            'issues_and_concerns' => ['nullable', 'string'],
            'ways_forward' => ['nullable', 'string'],
            'graduates_summary' => ['nullable', 'string'],
            'status' => ['required', Rule::in([AtarReport::STATUS_DRAFT, AtarReport::STATUS_FINAL])],

            'graduates_list' => ['nullable', 'array'],
            'graduates_list.*.code' => ['nullable', 'string', 'max:255'],
            'graduates_list.*.name' => ['nullable', 'string', 'max:255'],
            'graduates_list.*.gender' => ['nullable', 'string', 'max:50'],
            'graduates_list.*.agency' => ['nullable', 'string', 'max:255'],

            'dropouts_list' => ['nullable', 'array'],
            'dropouts_list.*.name' => ['nullable', 'string', 'max:255'],
            'dropouts_list.*.gender' => ['nullable', 'string', 'max:50'],
            'dropouts_list.*.agency' => ['nullable', 'string', 'max:255'],

            'lecturers_list' => ['nullable', 'array'],
            'lecturers_list.*.name' => ['nullable', 'string', 'max:255'],
            'lecturers_list.*.organization' => ['nullable', 'string', 'max:255'],
            'lecturers_list.*.role' => ['nullable', 'string', 'max:255'],

            'signatories' => ['nullable', 'array'],
            'signatories.*.role' => ['nullable', 'string', 'max:255'],
            'signatories.*.name' => ['nullable', 'string', 'max:255'],
            'signatories.*.title' => ['nullable', 'string', 'max:255'],

            'l1_modules' => ['nullable', 'array'],
            'l1_modules.*.module' => ['nullable', 'string', 'max:255'],
            'l1_modules.*.distribution' => ['nullable', 'array'],
            'l1_modules.*.distribution.*' => ['nullable', 'integer', 'min:0'],
            'l1_analysis' => ['nullable', 'string'],

            'l2_stats' => ['nullable', 'array'],
            'l2_stats.pretest.mean' => ['nullable', 'numeric'],
            'l2_stats.pretest.median' => ['nullable', 'numeric'],
            'l2_stats.pretest.mode' => ['nullable', 'numeric'],
            'l2_stats.pretest.min' => ['nullable', 'numeric'],
            'l2_stats.pretest.max' => ['nullable', 'numeric'],
            'l2_stats.pretest.count' => ['nullable', 'integer', 'min:0'],
            'l2_stats.posttest.mean' => ['nullable', 'numeric'],
            'l2_stats.posttest.median' => ['nullable', 'numeric'],
            'l2_stats.posttest.mode' => ['nullable', 'numeric'],
            'l2_stats.posttest.min' => ['nullable', 'numeric'],
            'l2_stats.posttest.max' => ['nullable', 'numeric'],
            'l2_stats.posttest.count' => ['nullable', 'integer', 'min:0'],
            'l2_analysis' => ['nullable', 'string'],

            'photos' => ['nullable', 'array'],
            'photos.*' => ['nullable', 'image', 'max:5120'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string'],
        ]);

        // Alpine always submits every row currently in its array, including
        // ones the admin added and then left untouched — drop anything
        // that's entirely blank so empty rows don't pollute the PDF tables.
        $validated['graduates_list'] = $this->dropEmptyRows($validated['graduates_list'] ?? []);
        $validated['dropouts_list'] = $this->dropEmptyRows($validated['dropouts_list'] ?? []);
        $validated['lecturers_list'] = $this->dropEmptyRows($validated['lecturers_list'] ?? []);
        $validated['signatories'] = $this->dropEmptyRows($validated['signatories'] ?? []);

        // The admin only edits raw 1-5 counts per module on the form — the
        // response count and average are always derived here rather than
        // trusted from the client, so they can never drift out of sync with
        // the counts actually saved.
        $validated['l1_modules'] = collect($validated['l1_modules'] ?? [])
            ->filter(fn ($row) => filled($row['module'] ?? null))
            ->map(function ($row) {
                $distribution = array_map('intval', $row['distribution'] ?? []);
                $distribution += [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
                ksort($distribution);
                $responses = array_sum($distribution);
                $weighted = 0;
                foreach ($distribution as $value => $count) {
                    $weighted += $value * $count;
                }

                return [
                    'module' => $row['module'],
                    'distribution' => $distribution,
                    'responses' => $responses,
                    'average' => $responses > 0 ? round($weighted / $responses, 2) : null,
                ];
            })
            ->values()
            ->all();

        // Photos accumulate across saves rather than being replaced wholesale
        // — remove_photos carries the paths of thumbnails the admin unchecked
        // for deletion, and any newly-uploaded files are appended after that.
        $photos = $atarReport->photos ?? [];

        // Only paths already on this report may be deleted — remove_photos
        // comes straight from the form, and without this check any file on
        // the public disk (certificates, other reports' photos) could be
        // deleted by submitting its path.
        $removals = array_values(array_intersect($validated['remove_photos'] ?? [], $photos));

        if ($removals !== []) {
            Storage::disk('public')->delete($removals);
            $photos = array_values(array_diff($photos, $removals));
        }

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $photos[] = $file->store('atar-reports', 'public');
            }
        }

        $validated['photos'] = $photos;
        unset($validated['remove_photos']);

        $atarReport->update($validated);

        return redirect()->route('admin.atar-reports.edit', $atarReport)->with('status', 'ATAR report saved.');
    }

    /**
     * Re-runs AtarReportGenerator against the linked training and overwrites
     * only the data-backed fields (attendees/graduates/dropouts/lecturers/
     * L1/L2) — narrative fields the admin has written are left alone. Useful
     * when certificates or evaluations are added/changed after the ATAR
     * draft was first generated.
     */
    public function recompute(Request $request, AtarReport $atarReport, AtarReportGenerator $generator): RedirectResponse
    {
        $this->authorizeView($atarReport, $request);

        abort_unless($atarReport->trainingRequest, 404);

        $atarReport->update($generator->recompute($atarReport->trainingRequest));

        return redirect()->route('admin.atar-reports.edit', $atarReport)
            ->with('status', 'Attendees, graduates, lecturers, and evaluation annexes recomputed from current training data.');
    }

    /**
     * Streams the report as a PDF (inline, not a forced download) so it can
     * be previewed in a new tab, printed, and wet-ink signed.
     */
    public function pdf(Request $request, AtarReport $atarReport): Response
    {
        $this->authorizeView($atarReport, $request);

        $atarReport->load('trainingRequest');

        return Pdf::loadView('pdf.atar-report', ['report' => $atarReport])
            ->setPaper('a4', 'portrait')
            ->stream(Str::slug($atarReport->title ?: 'atar-report').'.pdf');
    }

    public function destroy(AtarReport $atarReport): RedirectResponse
    {
        foreach ($atarReport->photos ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $atarReport->delete();

        return redirect()->route('admin.atar-reports.index')->with('status', 'ATAR report deleted.');
    }

    /**
     * The trainings a Regional Admin may base a new ATAR on: completed, with
     * an actual roster attached, and in their own region. Shared by create()
     * (which fills the dropdown) and store() (which re-checks what came back).
     */
    private function creatableTrainings(User $user): Builder
    {
        return TrainingRequest::completed()
            ->where(fn ($q) => $q->whereHas('participants')->orWhereNotNull('user_id'))
            ->when($user->isAdmin(), fn ($q) => $q->where('region', $user->region));
    }

    /**
     * Narrows a report query to what the viewer is allowed to see — every
     * region for a Super Admin, only their own for a Regional Admin.
     *
     * @param  Builder<AtarReport>  $query
     * @return Builder<AtarReport>
     */
    private function scopeToViewer(Builder $query, Request $request): Builder
    {
        return $query->when(
            $request->user()->isAdmin(),
            fn ($q) => $q->where('region', $request->user()->region)
        );
    }

    /**
     * The single-report counterpart to scopeToViewer(). 404 rather than 403
     * so a Regional Admin poking at ids can't learn which other regions'
     * reports exist.
     */
    private function authorizeView(AtarReport $atarReport, Request $request): void
    {
        if ($request->user()->isAdmin()) {
            abort_unless($atarReport->region === $request->user()->region, 404);
        }
    }

    /**
     * Drops any repeatable-row entry where every field is blank — Alpine
     * submits the full array as-is, including rows the admin added via
     * "+ Add row" and then never filled in or removed.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function dropEmptyRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => collect($row)->filter(fn ($value) => filled(trim((string) $value)))->isNotEmpty())
            ->values()
            ->all();
    }
}
