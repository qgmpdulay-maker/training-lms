<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingNeedsAssessment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingNeedsAssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $regionScope = fn ($query) => $query->when(
            $user->isAdmin(),
            fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('region', $user->region))
        );

        $submissions = TrainingNeedsAssessment::with('user')
            ->tap($regionScope)
            ->latest()
            ->paginate(20);

        return view('admin.training-needs-assessment', [
            'submissions' => $submissions,
            'trainingDemand' => $this->trainingDemand($regionScope),
        ]);
    }

    /**
     * What the TNA answers actually say participants need: how many submissions
     * were recommended each training, ranked highest-demand first.
     */
    private function trainingDemand(callable $regionScope): array
    {
        $counts = TrainingNeedsAssessment::query()
            ->tap($regionScope)
            ->whereNotNull('recommended_training_title')
            ->selectRaw('recommended_training_title, count(*) as count')
            ->groupBy('recommended_training_title')
            ->orderByDesc('count')
            ->get();

        $total = $counts->sum('count');

        $bars = $counts->map(fn ($row) => [
            'title' => $row->recommended_training_title,
            'count' => $row->count,
            'percent' => $total > 0 ? round(($row->count / $total) * 100) : 0,
        ])->values()->all();

        return [
            'total' => $total,
            'bars' => $bars,
            'top' => $bars[0] ?? null,
        ];
    }
}
