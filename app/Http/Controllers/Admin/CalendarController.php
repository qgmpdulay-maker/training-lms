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
    // TOR asks for APB/TA colour-coding at both the Regional and Central level.
    // One palette, used solid in both calendars — as the bar itself in the
    // month grid and as the row's accent in the all-regions agenda — so a
    // category reads the same wherever it appears. The agenda used to carry
    // its own pastel pills, which is half of why the two pages looked like
    // different products.
    const CATEGORY_BARS = [
        TrainingRequest::CATEGORY_APB => 'bg-blue-600 hover:bg-blue-700 text-white',
        TrainingRequest::CATEGORY_TA => 'bg-orange-500 hover:bg-orange-600 text-white',
    ];

    // The same two colours with nothing layered on them, for the flat accent
    // down the side of an agenda row and for the legend swatches.
    const CATEGORY_SWATCHES = [
        TrainingRequest::CATEGORY_APB => 'bg-blue-600',
        TrainingRequest::CATEGORY_TA => 'bg-orange-500',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search'));

        // One region in view means the month grid — an actual wall calendar,
        // which is what the regions asked for, with each training drawn as a
        // bar across the days it runs. That's always the case for a Regional
        // Admin, and for a Super Admin who has picked a region to look at.
        if ($user->isAdmin()) {
            return $this->monthView($request, $user->region, $search, canSchedule: true, regionPicker: false);
        }

        // Anything not in the configured list is treated as no choice at all,
        // so a hand-typed region can't quietly produce an empty calendar.
        $region = $request->query('region');
        $region = in_array($region, config('regions.list'), true) ? $region : null;

        if ($region !== null) {
            // Scheduling stays the Regional Admin's, so Central gets the same
            // grid without the Schedule button — and with a region picker,
            // which is their way back out to the whole country.
            return $this->monthView($request, $region, $search, canSchedule: false, regionPicker: true);
        }

        // Super Admin (Central), all regions at once.
        return $this->agendaView($request, $search);
    }

    /**
     * Central's whole-country view: the same month, the same controls and the
     * same palette as the grid, but listed by day instead of drawn as a wall
     * calendar. Eighteen regions of bars in one month grid is unreadable —
     * the region, not the shape of the week, is what Central is reading for.
     */
    private function agendaView(Request $request, string $search): View
    {
        $month = $this->requestedMonth($request);
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        // One month at a time, filtered in SQL rather than in PHP: the grid
        // can afford to load a region's whole history, but every region's is
        // thousands of rows. Overlap, not "starts this month", so a course
        // running in from last month still appears — the same rule the grid
        // uses, expressed against the database.
        $requests = TrainingRequest::query()
            ->when($search !== '', fn ($q) => $this->searchTrainings($q, $search))
            ->whereDate('preferred_date', '<=', $monthEnd)
            ->whereRaw('COALESCE(end_date, preferred_date) >= ?', [$monthStart->toDateString()])
            ->orderBy('preferred_date')
            ->orderBy('training_title')
            ->get();

        return view('admin.calendar', [
            'month' => $month,
            'days' => $this->agendaDays($requests, $monthStart),
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'monthCount' => $requests->count(),
            'categorySwatches' => self::CATEGORY_SWATCHES,
            'regions' => config('regions.list'),
            'selectableYears' => $this->selectableYears(null, $month),
            'search' => $search,
        ]);
    }

    /**
     * The month's trainings, bucketed under the day they appear on.
     *
     * A run that began last month is listed on the 1st rather than vanishing
     * or being filed under a date that isn't on screen; its row still shows
     * the true range, so nothing about it reads as starting that day.
     *
     * @param  Collection<int, TrainingRequest>  $requests
     * @return array<int, array<string, mixed>>
     */
    private function agendaDays(Collection $requests, Carbon $monthStart): array
    {
        $today = now()->startOfDay();

        return $requests
            ->groupBy(fn (TrainingRequest $r) => $r->preferred_date->lt($monthStart)
                ? $monthStart->toDateString()
                : $r->preferred_date->toDateString())
            ->sortKeys()
            ->map(fn (Collection $trainings, string $date) => [
                'date' => Carbon::parse($date),
                'isToday' => Carbon::parse($date)->isSameDay($today),
                'trainings' => $trainings,
            ])
            ->values()
            ->all();
    }

    /**
     * One region's month grid.
     *
     * Shared by both roles: the only differences are whether the Schedule
     * button is offered (Regional Admin only — Central can't create a
     * training) and whether a region picker is shown (Central only — a
     * Regional Admin has exactly one region and no choice to make).
     */
    private function monthView(Request $request, ?string $region, string $search, bool $canSchedule, bool $regionPicker): View
    {
        $month = $this->requestedMonth($request);

        $requests = TrainingRequest::where('region', $region)
            ->when($search !== '', fn ($q) => $this->searchTrainings($q, $search))
            ->get();

        return view('admin.calendar-month', [
            'month' => $month,
            'weeks' => $this->monthGrid($month, $requests),
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'categoryBars' => self::CATEGORY_BARS,
            'monthCount' => $this->overlapping($requests, $month->copy()->startOfMonth(), $month->copy()->endOfMonth())->count(),
            'search' => $search,
            'region' => $region,
            'canSchedule' => $canSchedule,
            'regionPicker' => $regionPicker,
            'regions' => config('regions.list'),
            'selectableYears' => $this->selectableYears($region, $month),
        ]);
    }

    /**
     * The years offered in the jump-to-month control.
     *
     * Taken from the region's own trainings rather than a fixed window —
     * paging back to 2024 through the arrows is twenty-odd clicks, and a
     * hardcoded range would either hide real history or pad the list with
     * years nothing ever happened in. Today's year and whatever year is open
     * are always included, so the control can't offer a list that excludes
     * what it is currently showing.
     *
     * @return array<int, int>
     */
    private function selectableYears(?string $region, Carbon $month): array
    {
        $bounds = TrainingRequest::query()
            // No region means Central's all-regions agenda, whose year list
            // should span the whole country rather than the rows with a null
            // region, which is what where('region', null) would ask for.
            ->when($region, fn ($q) => $q->where('region', $region))
            ->selectRaw('MIN(preferred_date) as first_day, MAX(COALESCE(end_date, preferred_date)) as last_day')
            ->first();

        $years = collect([now()->year, $month->year]);

        if ($bounds?->first_day) {
            $years->push((int) Carbon::parse($bounds->first_day)->year);
            $years->push((int) Carbon::parse($bounds->last_day)->year);
        }

        return range($years->min(), $years->max());
    }

    /**
     * Which month the grid is showing: ?month=YYYY-MM, else the current one.
     * Anything unparseable falls back to today rather than erroring — the
     * value only ever arrives from the page's own prev/next links.
     */
    private function requestedMonth(Request $request): Carbon
    {
        $requested = (string) $request->query('month');

        try {
            return $requested !== ''
                ? Carbon::createFromFormat('Y-m', $requested)->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    /**
     * The six-week grid behind the month view.
     *
     * Each week is laid out as its own CSS grid of seven columns, so a
     * training that runs Wednesday to the following Tuesday is drawn as two
     * bars — one ending Saturday, one resuming Sunday — rather than as a run
     * of disconnected per-day chips. The week is also where lanes are
     * assigned: two trainings on overlapping days need to sit on separate
     * rows within the cell, and a bar must keep the same lane for its whole
     * run across that week or it would appear to jump mid-span.
     *
     * @param  Collection<int, TrainingRequest>  $requests
     * @return array<int, array<string, mixed>>
     */
    private function monthGrid(Carbon $month, Collection $requests): array
    {
        // Sunday-first, matching the printed calendars the regions work from.
        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $visible = $this->overlapping($requests, $gridStart, $gridEnd);
        $today = now()->startOfDay();
        $weeks = [];

        for ($weekStart = $gridStart->copy(); $weekStart->lte($gridEnd); $weekStart->addWeek()) {
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SATURDAY);

            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $weekStart->copy()->addDays($i);
                $days[] = [
                    'date' => $date,
                    'inMonth' => $date->isSameMonth($month),
                    'isToday' => $date->isSameDay($today),
                    'isWeekend' => $date->isWeekend(),
                ];
            }

            $segments = $this->weekSegments($visible, $weekStart, $weekEnd);

            $weeks[] = [
                'days' => $days,
                'segments' => $segments,
                // At least one lane so every week row keeps the same minimum
                // height whether or not anything is scheduled in it.
                'lanes' => max(1, collect($segments)->max('lane') + 1),
            ];
        }

        return $weeks;
    }

    /**
     * The trainings touching one week, cut to that week's columns and stacked
     * into lanes.
     *
     * @param  Collection<int, TrainingRequest>  $visible
     * @return array<int, array<string, mixed>>
     */
    private function weekSegments(Collection $visible, Carbon $weekStart, Carbon $weekEnd): array
    {
        $inWeek = $visible
            ->filter(fn (TrainingRequest $r) => $r->preferred_date->lte($weekEnd) && $r->endDate()->gte($weekStart))
            // Longest run first so the bars that cross the most days settle
            // into the top lanes and the week reads as bands, not a staircase.
            ->sortBy([
                fn (TrainingRequest $a, TrainingRequest $b) => $a->preferred_date <=> $b->preferred_date,
                fn (TrainingRequest $a, TrainingRequest $b) => $b->dayCount() <=> $a->dayCount(),
            ]);

        // lanes[n] = the column after the last bar placed in lane n, so a new
        // bar can take that lane only once its start clears it.
        $lanes = [];
        $segments = [];

        foreach ($inWeek as $training) {
            $start = $training->preferred_date->greaterThan($weekStart) ? $training->preferred_date : $weekStart;
            $end = $training->endDate()->lessThan($weekEnd) ? $training->endDate() : $weekEnd;

            // 1-based, because that is what CSS grid-column wants. Cast
            // because Carbon 3 hands back a float, which would render as
            // "grid-column: 3.0" and be dropped by the browser.
            $startColumn = (int) $weekStart->diffInDays($start) + 1;
            $span = (int) $start->diffInDays($end) + 1;

            $lane = 0;
            while (isset($lanes[$lane]) && $lanes[$lane] > $startColumn) {
                $lane++;
            }
            $lanes[$lane] = $startColumn + $span;

            $segments[] = [
                'training' => $training,
                'startColumn' => $startColumn,
                'span' => $span,
                'lane' => $lane,
                // Squared-off ends tell the reader the run carries on into the
                // week above or below rather than stopping on Saturday.
                'continuesBefore' => $training->preferred_date->lt($weekStart),
                'continuesAfter' => $training->endDate()->gt($weekEnd),
            ];
        }

        return $segments;
    }

    /**
     * Trainings whose run overlaps the window at all — not just those that
     * start inside it, or a course running from last month would vanish.
     *
     * @param  Collection<int, TrainingRequest>  $requests
     * @return Collection<int, TrainingRequest>
     */
    private function overlapping(Collection $requests, Carbon $from, Carbon $until): Collection
    {
        return $requests->filter(
            fn (TrainingRequest $r) => $r->preferred_date->lte($until) && $r->endDate()->gte($from)
        );
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
}
