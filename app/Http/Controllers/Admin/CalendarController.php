<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    // TOR asks for APB/TA color-coding at both the Regional and Central level —
    // one shared palette so a category reads the same everywhere it appears.
    const CATEGORY_COLORS = [
        TrainingRequest::CATEGORY_APB => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700',
        TrainingRequest::CATEGORY_TA => 'bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search'));

        if ($user->isAdmin()) {
            $requests = TrainingRequest::where('region', $user->region)
                ->when($search !== '', fn ($q) => $this->searchTrainings($q, $search))
                ->orderBy('preferred_date')
                ->get();

            $groupedByMonth = $this->groupEntries($requests);

            return view('admin.calendar', [
                'groupedByMonth' => $groupedByMonth,
                'defaultMonth' => $this->defaultMonth($groupedByMonth),
                'categoryColors' => self::CATEGORY_COLORS,
                'regions' => config('regions.list'),
                'search' => $search,
                'filters' => null,
            ]);
        }

        // Super Admin (Central) sees every region — filterable by region, plus
        // a free-text search over training/agency so the whole-country agenda
        // stays usable at scale.
        $filters = [
            'region' => $request->query('region') ?: null,
        ];

        $requests = TrainingRequest::query()
            ->when($filters['region'], fn ($q) => $q->where('region', $filters['region']))
            ->when($search !== '', fn ($q) => $this->searchTrainings($q, $search))
            ->orderBy('preferred_date')
            ->get();

        $groupedByMonth = $this->groupEntries($requests);

        return view('admin.calendar', [
            'groupedByMonth' => $groupedByMonth,
            'defaultMonth' => $this->defaultMonth($groupedByMonth),
            'categoryColors' => self::CATEGORY_COLORS,
            'regions' => config('regions.list'),
            'search' => $search,
            'filters' => $filters,
        ]);
    }

    /**
     * Free-text match on training name or requesting agency (e.g. "PCO",
     * "TA for LGU/NGA") — replaces the old exact-title dropdown so the
     * calendar can be searched the way people actually think of a training.
     */
    private function searchTrainings($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('training_title', 'like', "%{$search}%")
                ->orWhere('requesting_agency', 'like', "%{$search}%");
        });
    }

    /**
     * Which month tab opens by default: this month if it has anything on it,
     * else the nearest upcoming month, else the most recent past one — so the
     * calendar never opens on a stale, empty first tab. Relies on
     * groupEntries() already returning months in chronological key order.
     */
    private function defaultMonth(Collection $groupedByMonth): ?string
    {
        if ($groupedByMonth->isEmpty()) {
            return null;
        }

        $currentLabel = now()->format('F Y');
        if ($groupedByMonth->has($currentLabel)) {
            return $currentLabel;
        }

        $monthStart = now()->startOfMonth();
        $upcoming = $groupedByMonth->keys()
            ->first(fn (string $label) => Carbon::createFromFormat('F Y', $label)->startOfMonth()->gte($monthStart));

        return $upcoming ?? $groupedByMonth->keys()->last();
    }

    /**
     * Groups training requests by month so the agenda view can tab between
     * months. Sorting before grouping keeps each month's entries in date
     * order, and the months themselves fall in chronological order since
     * groupBy preserves first-seen order.
     *
     * @param  Collection<int, TrainingRequest>  $requests
     */
    private function groupEntries(Collection $requests): Collection
    {
        return $requests
            ->sortBy('preferred_date')
            ->groupBy(fn (TrainingRequest $request) => $request->preferred_date->format('F Y'));
    }
}
