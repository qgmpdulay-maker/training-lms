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
        $region = $user->isAdmin() ? $user->region : null;

        $filesRecords = TrainingRequest::with(['user', 'participants', 'trainingEvaluation'])
            ->when($region, fn ($query) => $query->where('region', $region))
            ->orderByDesc('preferred_date')
            ->paginate(10, ['*'], 'files');

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
            'graduatesByTraining' => $graduatesByTraining,
            'filesRecords' => $filesRecords,
            'statusDonut' => $this->statusDonut($region),
            'statusBars' => $this->statusBars($region),
            'evaluationSummaries' => $this->evaluationSummaries($region),
            'graduatesByLgu' => $this->graduatesByLgu($region),
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

    private function evaluationSummaries(?string $region): array
    {
        return TrainingRequest::whereHas('trainingEvaluation')
            ->when($region, fn ($query) => $query->where('region', $region))
            ->with('trainingEvaluation')
            ->get()
            ->groupBy('training_title')
            ->map(function ($requests, $trainingTitle) {
                $evaluations = $requests->pluck('trainingEvaluation');
                $moduleRatings = $evaluations->pluck('module_ratings')->filter()->flatten(1);

                $modules = $moduleRatings->groupBy('module')->map(function ($rows, $module) {
                    $moduleScores = $rows->pluck('module_rating')->filter(fn ($r) => is_numeric($r));
                    $trainerScores = $rows->pluck('trainer_rating')->filter(fn ($r) => is_numeric($r));

                    return [
                        'module' => $module,
                        'avg_module_rating' => $moduleScores->isNotEmpty() ? round($moduleScores->avg(), 2) : null,
                        'avg_trainer_rating' => $trainerScores->isNotEmpty() ? round($trainerScores->avg(), 2) : null,
                        'comments' => $rows->pluck('comment')->filter()->values()->all(),
                    ];
                })->values();

                $overallTrainerScores = $moduleRatings->pluck('trainer_rating')->filter(fn ($r) => is_numeric($r));

                return [
                    'training_title' => $trainingTitle,
                    'evaluation_count' => $evaluations->count(),
                    'modules' => $modules,
                    'overall_trainer_rating' => $overallTrainerScores->isNotEmpty() ? round($overallTrainerScores->avg(), 2) : null,
                    'pretest' => $this->scoreStats($evaluations->pluck('pretest_score')->filter(fn ($v) => $v !== null)),
                    'posttest' => $this->scoreStats($evaluations->pluck('posttest_score')->filter(fn ($v) => $v !== null)),
                ];
            })
            ->values()
            ->all();
    }

    private function scoreStats($scores): array
    {
        $sorted = $scores->map(fn ($v) => (float) $v)->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return ['count' => 0, 'mean' => null, 'median' => null, 'mode' => null, 'min' => null, 'max' => null];
        }

        $median = $count % 2 === 0
            ? ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2
            : $sorted[intdiv($count, 2)];

        $frequency = $sorted->countBy();
        $maxFrequency = $frequency->max();
        $mode = $frequency->filter(fn ($f) => $f === $maxFrequency)->keys()->sort()->first();

        return [
            'count' => $count,
            'mean' => round($sorted->avg(), 2),
            'median' => round($median, 2),
            'mode' => round((float) $mode, 2),
            'min' => $sorted->first(),
            'max' => $sorted->last(),
        ];
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
}
