<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\TrainingRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class CalendarController extends Controller
{
    // Same colors used elsewhere so an event type reads consistently across the app.
    const EVENT_TYPE_COLORS = [
        CalendarEvent::TYPE_HOLIDAY => 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700',
        CalendarEvent::TYPE_SUSPENSION => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700',
        CalendarEvent::TYPE_OTHER => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600',
    ];

    // TOR asks for APB/TA color-coding at both the Regional and Central level —
    // one shared palette so a category reads the same everywhere it appears.
    const CATEGORY_COLORS = [
        TrainingRequest::CATEGORY_APB => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700',
        TrainingRequest::CATEGORY_TA => 'bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $events = CalendarEvent::with('creator')
                ->where(fn ($q) => $q->whereNull('region')->orWhere('region', $user->region))
                ->orderBy('date')
                ->get();

            $requests = TrainingRequest::where('region', $user->region)
                ->orderBy('preferred_date')
                ->get();

            $groupedByMonth = $this->groupEntries($requests, $events);

            return view('admin.calendar', [
                'groupedByMonth' => $groupedByMonth,
                'defaultMonth' => $this->defaultMonth($groupedByMonth),
                'categoryColors' => self::CATEGORY_COLORS,
                'eventTypeColors' => self::EVENT_TYPE_COLORS,
                'eventTypeLabels' => CalendarEvent::$typeLabels,
                'regions' => config('regions.list'),
                'categoryLabels' => TrainingRequest::$categoryLabels,
                'trainingTitles' => collect(config('trainings.catalog'))->pluck('title'),
                'filters' => null,
            ]);
        }

        // Super Admin (Central) sees every region — filterable by region, training
        // type, and category so the whole-country agenda stays usable at scale.
        $filters = [
            'region' => $request->query('region') ?: null,
            'category' => $request->query('category') ?: null,
            'training_title' => $request->query('training_title') ?: null,
        ];

        $events = CalendarEvent::with('creator')
            ->when($filters['region'], fn ($q) => $q->where(fn ($qq) => $qq->whereNull('region')->orWhere('region', $filters['region'])))
            ->orderBy('date')
            ->get();

        $requests = TrainingRequest::query()
            ->when($filters['region'], fn ($q) => $q->where('region', $filters['region']))
            ->when($filters['category'], fn ($q) => $q->where('category', $filters['category']))
            ->when($filters['training_title'], fn ($q) => $q->where('training_title', $filters['training_title']))
            ->orderBy('preferred_date')
            ->get();

        $groupedByMonth = $this->groupEntries($requests, $events);

        return view('admin.calendar', [
            'groupedByMonth' => $groupedByMonth,
            'defaultMonth' => $this->defaultMonth($groupedByMonth),
            'categoryColors' => self::CATEGORY_COLORS,
            'eventTypeColors' => self::EVENT_TYPE_COLORS,
            'eventTypeLabels' => CalendarEvent::$typeLabels,
            'regions' => config('regions.list'),
            'categoryLabels' => TrainingRequest::$categoryLabels,
            'trainingTitles' => collect(config('trainings.catalog'))->pluck('title'),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(CalendarEvent::$typeLabels))],
            'date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'region' => ['nullable', 'string', 'in:'.implode(',', config('regions.list'))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['created_by'] = $request->user()->id;

        CalendarEvent::create($validated);

        return Redirect::route('admin.calendar')->with('status', "\"{$validated['title']}\" was added to the calendar.");
    }

    public function destroy(CalendarEvent $calendarEvent): RedirectResponse
    {
        $calendarEvent->delete();

        return Redirect::route('admin.calendar')->with('status', "\"{$calendarEvent->title}\" was removed from the calendar.");
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
     * Merge training requests and calendar events into one collection, grouped
     * and ordered by month so both appear together in the same agenda view.
     *
     * @param  \Illuminate\Support\Collection<int, TrainingRequest>  $requests
     * @param  \Illuminate\Support\Collection<int, CalendarEvent>  $events
     */
    private function groupEntries($requests, $events)
    {
        $trainingEntries = $requests->map(fn (TrainingRequest $request) => (object) [
            'kind' => 'training',
            'date' => $request->preferred_date,
            'model' => $request,
        ]);

        $eventEntries = $events->map(fn (CalendarEvent $event) => (object) [
            'kind' => 'event',
            'date' => $event->date,
            'model' => $event,
        ]);

        // Sorting before grouping keeps each month's entries in date order, and
        // the months themselves fall in chronological order since groupBy
        // preserves first-seen order.
        return $trainingEntries->concat($eventEntries)
            ->sortBy('date')
            ->groupBy(fn ($entry) => $entry->date->format('F Y'));
    }
}
