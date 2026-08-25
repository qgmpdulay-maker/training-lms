<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolsController extends Controller
{
    // Status-semantic colors from the design system's fixed status palette,
    // plus one categorical slot (blue) and one neutral for the two statuses
    // that aren't strictly good/warning/critical.
    private const STATUS_COLORS = [
        TrainingRequest::STATUS_SUBMITTED => '#9ca3af',
        TrainingRequest::STATUS_UNDER_REVIEW => '#fab219',
        TrainingRequest::STATUS_APPROVED => '#2a78d6',
        TrainingRequest::STATUS_DECLINED => '#d03b3b',
        TrainingRequest::STATUS_COMPLETED => '#0ca30c',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $region = $user->isAdmin() ? $user->region : ($user->isSuperAdmin() ? $request->query('region') : null);

        $filesRecords = TrainingRequest::with(['user', 'participants', 'trainingEvaluation'])
            ->when($region, fn ($query) => $query->where('region', $region))
            ->orderByDesc('preferred_date')
            ->paginate(10, ['*'], 'files')
            ->withQueryString();

        // The files table paginates via plain links; fetch() re-requests this same
        // route and swaps in just the table so paging doesn't reload the whole page.
        if ($request->ajax()) {
            return view('admin.partials.files-table', compact('filesRecords'));
        }

        $graduatesByTraining = TrainingRequest::where('status', TrainingRequest::STATUS_COMPLETED)
            ->when($region, fn ($query) => $query->where('region', $region))
            ->with('participants')
            ->get()
            ->groupBy('training_title')
            ->map(fn ($records) => [
                'total' => $records->sum(fn (TrainingRequest $r) => max($r->participants->count(), 1)),
                'byYear' => $records->groupBy(fn (TrainingRequest $r) => $r->preferred_date->format('Y'))
                    ->map(fn ($yearRecords) => $yearRecords->sum(fn (TrainingRequest $r) => max($r->participants->count(), 1)))
                    ->sortKeys(),
            ])
            ->sortKeys();

        return view('admin.tools', [
            'region' => $region,
            'regionLocked' => $user->isAdmin(),
            'regions' => config('regions.list'),
            'graduatesByTraining' => $graduatesByTraining,
            'filesRecords' => $filesRecords,
            'statusDonut' => $this->statusDonut($region),
            'statusBars' => $this->statusBars($region),
            'evaluationsByTraining' => $this->evaluationSummaries($region),
            'graduatesByLgu' => $this->graduatesByLgu($region),
            'graduatesByRegion' => $this->graduatesByRegion($region),
        ]);
    }

    public function uploadFiles(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isAdmin() && $trainingRequest->region !== $user->region, 403);

        $validated = $request->validate([
            'certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'atar_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        if ($request->hasFile('certificate_file')) {
            $trainingRequest->certificate_file_path = $validated['certificate_file']->store('certificates', 'public');
        }

        if ($request->hasFile('atar_file')) {
            $trainingRequest->atar_file_path = $validated['atar_file']->store('atar', 'public');
        }

        $trainingRequest->save();

        return back()->with('status', "Files updated for {$trainingRequest->training_title}.");
    }

    private function statusDonut(?string $region): array
    {
        $total = TrainingRequest::when($region, fn ($query) => $query->where('region', $region))->count();
        $accomplished = TrainingRequest::where('status', TrainingRequest::STATUS_COMPLETED)
            ->when($region, fn ($query) => $query->where('region', $region))
            ->count();
        $pending = $total - $accomplished;

        $radius = 40;
        $circumference = 2 * M_PI * $radius;
        $accomplishedFraction = $total > 0 ? $accomplished / $total : 0;
        $accomplishedArc = $accomplishedFraction * $circumference;

        return [
            'total' => $total,
            'circumference' => $circumference,
            'radius' => $radius,
            'segments' => [
                [
                    'label' => 'Accomplished',
                    'value' => $accomplished,
                    'percent' => $total > 0 ? round($accomplishedFraction * 100) : 0,
                    'color' => self::STATUS_COLORS[TrainingRequest::STATUS_COMPLETED],
                    'dasharray' => "{$accomplishedArc} {$circumference}",
                    'dashoffset' => 0,
                ],
                [
                    'label' => 'Pending',
                    'value' => $pending,
                    'percent' => $total > 0 ? 100 - round($accomplishedFraction * 100) : 0,
                    'color' => '#9ca3af',
                    'dasharray' => ($circumference - $accomplishedArc).' '.$circumference,
                    'dashoffset' => -$accomplishedArc,
                ],
            ],
        ];
    }

    private function statusBars(?string $region): array
    {
        $counts = TrainingRequest::selectRaw('status, count(*) as count')
            ->when($region, fn ($query) => $query->where('region', $region))
            ->groupBy('status')
            ->pluck('count', 'status');

        $maxCount = max($counts->max(), 1);

        return collect(TrainingRequest::$statusLabels)
            ->map(fn ($label, $status) => [
                'label' => $label,
                'value' => $counts[$status] ?? 0,
                'percent' => round((($counts[$status] ?? 0) / $maxCount) * 100),
                'color' => self::STATUS_COLORS[$status],
            ])
            ->values()
            ->all();
    }

    /**
     * Evaluated sessions grouped by training title (one tab per title on the
     * Tools page), each session listed separately underneath — every session
     * gets exactly one evaluation entered via "Add Evaluation", so pooling
     * several sessions of the same title into shared stats mixed unrelated
     * runs' pre/post-test scores into meaningless combined figures.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function evaluationSummaries(?string $region): array
    {
        return TrainingRequest::whereHas('trainingEvaluation')
            ->when($region, fn ($query) => $query->where('region', $region))
            ->with('trainingEvaluation')
            ->orderByDesc('preferred_date')
            ->get()
            ->map(function (TrainingRequest $trainingRequest) {
                $evaluation = $trainingRequest->trainingEvaluation;
                $moduleRatings = collect($evaluation->module_ratings ?? []);

                $modules = $moduleRatings->groupBy('module')->map(function ($rows, $module) {
                    $moduleScores = $rows->pluck('module_rating')->filter(fn ($r) => is_numeric($r));
                    $trainerScores = $rows->pluck('trainer_rating')->filter(fn ($r) => is_numeric($r));

                    return [
                        'module' => $module,
                        'module_rating' => $moduleScores->isNotEmpty() ? round($moduleScores->avg(), 2) : null,
                        'trainer_rating' => $trainerScores->isNotEmpty() ? round($trainerScores->avg(), 2) : null,
                    ];
                })->values();

                $trainerScores = $moduleRatings->pluck('trainer_rating')->filter(fn ($r) => is_numeric($r));

                return [
                    'training_request_id' => $trainingRequest->id,
                    'training_title' => $trainingRequest->training_title,
                    'preferred_date' => $trainingRequest->preferred_date,
                    'venue' => $trainingRequest->venue,
                    'updated_at' => $evaluation->updated_at,
                    'modules' => $modules,
                    'overall_trainer_rating' => $trainerScores->isNotEmpty() ? round($trainerScores->avg(), 2) : null,
                    'pretest_score' => $evaluation->pretest_score,
                    'posttest_score' => $evaluation->posttest_score,
                ];
            })
            ->groupBy('training_title')
            ->sortKeys()
            ->all();
    }

    private function graduatesByLgu(?string $region): array
    {
        return TrainingRequest::where('status', TrainingRequest::STATUS_COMPLETED)
            ->whereNotNull('lgu')
            ->when($region, fn ($query) => $query->where('region', $region))
            ->with('participants')
            ->get()
            ->groupBy('lgu')
            ->map(fn ($records, $lgu) => ['lgu' => $lgu, 'total' => $records->sum(fn (TrainingRequest $r) => max($r->participants->count(), 1))])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * Graduates and trainings per OCD region, keyed by region name, for the
     * choropleth map on the Graduates by LGU card. Regions with no completed
     * trainings are simply absent from the result.
     */
    private function graduatesByRegion(?string $region): array
    {
        return TrainingRequest::where('status', TrainingRequest::STATUS_COMPLETED)
            ->whereNotNull('region')
            ->when($region, fn ($query) => $query->where('region', $region))
            ->with('participants')
            ->get()
            ->groupBy('region')
            ->map(fn ($records) => [
                'graduates' => $records->sum(fn (TrainingRequest $r) => max($r->participants->count(), 1)),
                'trainings' => $records->count(),
            ])
            ->all();
    }
}
