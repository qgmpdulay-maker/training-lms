<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingNeedsAssessment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TrainingNeedsAssessmentController extends Controller
{
    /** How a city or region groups its submissions on this page. */
    const GROUP_CITY = 'city';

    const GROUP_REGION = 'region';

    public function index(Request $request): View
    {
        $user = $request->user();
        $region = $user->isSuperAdmin() ? $request->query('region') : null;
        $search = trim((string) $request->query('tna_q'));

        // Needs are read per area, not per person: one participant's answers say
        // little on their own, while "17 submissions from Baguio City, mostly
        // Emergency Response" is what a training plan is built from. City is the
        // default because that is the level trainings are actually run at.
        $groupBy = $request->query('group') === self::GROUP_REGION
            ? self::GROUP_REGION
            : self::GROUP_CITY;

        $groups = $this->groupedNeeds($user, $region, $groupBy, $search);

        $page = LengthAwarePaginator::resolveCurrentPage('needs_page');
        $paginatedGroups = new LengthAwarePaginator(
            $groups->forPage($page, 15)->values(),
            $groups->count(),
            15,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'needs_page'],
        );
        $paginatedGroups->fragment('tna-submissions');

        $payload = [
            'groups' => $paginatedGroups,
            'groupBy' => $groupBy,
            'submissionTotal' => $groups->sum('submissions'),
            'submissionSearch' => $search,
            'regions' => config('regions.list'),
            'selectedRegion' => $region,
        ];

        // The search box re-requests this same route and swaps in just the
        // results, so typing (or paging) doesn't reload the whole page — see
        // resources/views/admin/partials/live-search-script.blade.php.
        if ($request->ajax() && $request->query('_section') === 'tna-submissions') {
            return view('admin.partials.tna-submissions-results', $payload);
        }

        return view('admin.training-needs-assessment', $payload);
    }

    /**
     * One row per city (or per region): how many assessments came from there,
     * how many people sent them, and what those people asked for.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function groupedNeeds($user, ?string $region, string $groupBy, string $search): Collection
    {
        $column = $groupBy === self::GROUP_REGION ? 'users.region' : 'users.city';

        $scope = fn ($query) => $query
            ->join('users', 'users.id', '=', 'training_needs_assessments.user_id')
            // A Regional Admin reads their own region; the Super Admin's region
            // filter narrows the same way.
            ->when($user->isAdmin(), fn ($q) => $q->where('users.region', $user->region))
            ->when($region, fn ($q) => $q->where('users.region', $region))
            // In this view the search is over the areas, since that is what the
            // rows are — the participant names behind them are not shown.
            ->when($search !== '', fn ($q) => $q->where($column, 'like', "%{$search}%"));

        // Totals per area, and separately the spread of what they asked for.
        // Two queries rather than one, because counting distinct participants
        // per area and counting submissions per category need different grouping.
        $totals = TrainingNeedsAssessment::query()
            ->tap($scope)
            ->selectRaw("{$column} as area, users.region as region, count(*) as submissions, count(distinct training_needs_assessments.user_id) as participants, max(training_needs_assessments.created_at) as latest")
            ->groupBy('area', 'region')
            ->get();

        $categories = TrainingNeedsAssessment::query()
            ->tap($scope)
            ->selectRaw("{$column} as area, training_needs_assessments.top_category as category, count(*) as total")
            ->groupBy('area', 'category')
            ->get()
            ->groupBy('area');

        $trainings = TrainingNeedsAssessment::query()
            ->tap($scope)
            ->whereNotNull('training_needs_assessments.recommended_training_title')
            ->selectRaw("{$column} as area, training_needs_assessments.recommended_training_title as training, count(*) as total")
            ->groupBy('area', 'training')
            ->get()
            ->groupBy('area');

        return $totals
            ->map(fn ($row) => [
                // A participant with no city on file still counts — their row is
                // labelled rather than dropped, so the totals stay honest.
                'area' => $row->area ?: __('Not specified'),
                'region' => $row->region,
                'submissions' => (int) $row->submissions,
                'participants' => (int) $row->participants,
                'latest' => $row->latest,
                'top_needs' => collect($categories[$row->area] ?? [])
                    ->filter(fn ($c) => filled($c->category))
                    ->sortByDesc('total')
                    ->take(3)
                    ->map(fn ($c) => ['category' => $c->category, 'total' => (int) $c->total])
                    ->values()
                    ->all(),
                'top_training' => collect($trainings[$row->area] ?? [])
                    ->sortByDesc('total')
                    ->first()?->training,
            ])
            ->sortByDesc('submissions')
            ->values();
    }
}
