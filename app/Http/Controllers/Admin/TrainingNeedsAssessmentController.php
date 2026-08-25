<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingNeedsAssessment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class TrainingNeedsAssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $region = $user->isSuperAdmin() ? $request->query('region') : null;
        $search = trim((string) $request->query('tna_q'));

        $regionScope = fn ($query) => $query
            ->when(
                $user->isAdmin(),
                fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('region', $user->region))
            )
            ->when(
                $region,
                fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('region', $region))
            );

        $submissions = TrainingNeedsAssessment::with('user')
            ->tap($regionScope)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('top_category', 'like', "%{$search}%")
                        ->orWhereRaw("DATE_FORMAT(created_at, '%b %d, %Y') like ?", ["%{$search}%"])
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%")
                                ->orWhere('organization', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'submissions_page')
            ->withQueryString()
            ->fragment('tna-submissions');

        return view('admin.training-needs-assessment', [
            'submissions' => $submissions,
            'submissionSearch' => $search,
            'trainingDemand' => $this->trainingDemand($regionScope),
            'organizationBreakdown' => $user->isSuperAdmin() ? $this->organizationBreakdown($regionScope, $request) : null,
            'regions' => config('regions.list'),
            'selectedRegion' => $region,
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

    /**
     * TNA demand grouped by the participant's LGU/organization, per the TOR
     * requirement to generate needs-assessment data per LGU/organization.
     */
    private function organizationBreakdown(callable $regionScope, Request $request): LengthAwarePaginator
    {
        $submissions = TrainingNeedsAssessment::with('user')
            ->tap($regionScope)
            ->get();

        $rows = $submissions
            ->groupBy(fn (TrainingNeedsAssessment $tna) => $tna->user->organization ?: 'Unspecified organization')
            ->map(function ($rows, $organization) {
                $topTraining = $rows->pluck('recommended_training_title')
                    ->filter()
                    ->countBy()
                    ->sortDesc()
                    ->keys()
                    ->first();

                return [
                    'organization' => $organization,
                    'region' => $rows->pluck('user.region')->filter()->unique()->sort()->implode(', ') ?: '—',
                    'submissions' => $rows->count(),
                    'top_training' => $topTraining,
                ];
            })
            ->sortByDesc('submissions')
            ->values();

        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage('org_page');

        return (new LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'org_page']
        ))->fragment('org-breakdown');
    }
}
