<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\TrainingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function index(Request $request): View
    {
        $user = $request->user();

        $events = CalendarEvent::with('creator')
            ->when($user->isAdmin(), fn ($query) => $query->where(function ($q) use ($user) {
                $q->whereNull('region')->orWhere('region', $user->region);
            }))
            ->orderBy('date')
            ->get();

        if ($user->isAdmin()) {
            $requests = TrainingRequest::where('region', $user->region)
                ->orderBy('preferred_date')
                ->get();

            $groupedByMonth = $this->groupEntries($requests, $events);

            return view('admin.calendar', [
                'groupedByMonth' => $groupedByMonth,
                'colorBy' => 'category',
                'categoryColors' => [
                    TrainingRequest::CATEGORY_APB => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700',
                    TrainingRequest::CATEGORY_TA => 'bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700',
                ],
                'eventTypeColors' => self::EVENT_TYPE_COLORS,
                'eventTypeLabels' => CalendarEvent::$typeLabels,
                'regions' => config('regions.list'),
            ]);
        }

        $requests = TrainingRequest::orderBy('preferred_date')->get();

        $groupedByMonth = $this->groupEntries($requests, $events);

        return view('admin.calendar', [
            'groupedByMonth' => $groupedByMonth,
            'colorBy' => 'status',
            'statusColors' => [
                TrainingRequest::STATUS_SUBMITTED => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600',
                TrainingRequest::STATUS_UNDER_REVIEW => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700',
                TrainingRequest::STATUS_APPROVED => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700',
                TrainingRequest::STATUS_DECLINED => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700',
                TrainingRequest::STATUS_COMPLETED => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700',
            ],
            'eventTypeColors' => self::EVENT_TYPE_COLORS,
            'eventTypeLabels' => CalendarEvent::$typeLabels,
            'regions' => config('regions.list'),
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
