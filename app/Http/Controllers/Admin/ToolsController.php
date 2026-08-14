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
        $filesRecords = TrainingRequest::with('user')
            ->orderByDesc('preferred_date')
            ->paginate(10, ['*'], 'files');

        // The files table paginates via plain links; fetch() re-requests this same
        // route and swaps in just the table so paging doesn't reload the whole page.
        if ($request->ajax()) {
            return view('admin.partials.files-table', compact('filesRecords'));
        }

        $graduatesByTraining = TrainingRequest::where('status', TrainingRequest::STATUS_COMPLETED)
            ->get()
            ->groupBy('training_title')
            ->map(fn ($records) => [
                'total' => $records->count(),
                'byYear' => $records->groupBy(fn (TrainingRequest $r) => $r->preferred_date->format('Y'))->map->count()->sortKeys(),
            ])
            ->sortKeys();

        $notYetAvailable = [
            ['title' => 'Evaluation Computation (L1 / L2)', 'description' => 'Auto-computed module and trainer ratings, pre/post-test statistics.'],
            ['title' => 'Map of Graduates and Teams Organized', 'description' => 'Graduates and teams organized per LGU, volunteers, and RDRRMC member agencies.'],
        ];

        return view('admin.tools', [
            'graduatesByTraining' => $graduatesByTraining,
            'notYetAvailable' => $notYetAvailable,
            'filesRecords' => $filesRecords,
            'statusDonut' => $this->statusDonut(),
            'statusBars' => $this->statusBars(),
        ]);
    }

    public function uploadFiles(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
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

        return back()->with('status', "Files updated for {$trainingRequest->user->name}.");
    }

    private function statusDonut(): array
    {
        $total = TrainingRequest::count();
        $accomplished = TrainingRequest::where('status', TrainingRequest::STATUS_COMPLETED)->count();
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

    private function statusBars(): array
    {
        $counts = TrainingRequest::selectRaw('status, count(*) as count')
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
}
