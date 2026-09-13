<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AtarReport;
use App\Models\TrainingRequest;
use App\Services\AtarReportGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
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
 */
class AtarReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = AtarReport::with('trainingRequest')
            ->when(trim((string) $request->query('q')) !== '', fn ($q) => $q->where('title', 'like', '%'.trim($request->query('q')).'%'))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.super-admin.atar-reports.index', [
            'reports' => $reports,
            'search' => $request->query('q'),
        ]);
    }

    /**
     * Shows the "generate from a training" vs "start blank" choice, with
     * every completed training available to generate from.
     */
    public function create(): View
    {
        $trainingRequests = TrainingRequest::completed()
            ->orderByDesc('preferred_date')
            ->get(['id', 'training_title', 'venue', 'preferred_date', 'region']);

        return view('admin.super-admin.atar-reports.create', [
            'trainingRequests' => $trainingRequests,
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

        if ($validated['mode'] === 'generate') {
            $trainingRequest = TrainingRequest::completed()->findOrFail($validated['training_request_id']);
            $data = $generator->generate($trainingRequest);
        } else {
            $data = [
                'status' => AtarReport::STATUS_DRAFT,
                'signatories' => [
                    ['role' => 'Prepared by', 'name' => '', 'title' => ''],
                    ['role' => 'Approved by', 'name' => '', 'title' => ''],
                ],
            ];
        }

        $data['created_by'] = $request->user()->id;

        $report = AtarReport::create($data);

        return redirect()->route('admin.atar-reports.edit', $report)
            ->with('status', $validated['mode'] === 'generate'
                ? 'ATAR generated from training data — review and complete the narrative sections.'
                : 'Blank ATAR created — fill in the details below.');
    }

    public function edit(AtarReport $atarReport): View
    {
        $atarReport->load('trainingRequest');

        return view('admin.super-admin.atar-reports.edit', [
            'report' => $atarReport,
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

        if (! empty($validated['remove_photos'])) {
            foreach ($validated['remove_photos'] as $path) {
                Storage::disk('public')->delete($path);
            }
            $photos = collect($photos)->reject(fn ($path) => in_array($path, $validated['remove_photos'], true))->values()->all();
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
    public function recompute(AtarReport $atarReport, AtarReportGenerator $generator): RedirectResponse
    {
        abort_unless($atarReport->trainingRequest, 404);

        $atarReport->update($generator->recompute($atarReport->trainingRequest));

        return redirect()->route('admin.atar-reports.edit', $atarReport)
            ->with('status', 'Attendees, graduates, lecturers, and evaluation annexes recomputed from current training data.');
    }

    /**
     * Streams the report as a PDF (inline, not a forced download) so it can
     * be previewed in a new tab, printed, and wet-ink signed.
     */
    public function pdf(AtarReport $atarReport): Response
    {
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
