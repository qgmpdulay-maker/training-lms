<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        $groupedByMonth = TrainingRequest::with('user')
            ->orderBy('preferred_date')
            ->get()
            ->groupBy(fn (TrainingRequest $request) => $request->preferred_date->format('F Y'));

        return view('admin.calendar', [
            'groupedByMonth' => $groupedByMonth,
            'statusColors' => [
                TrainingRequest::STATUS_SUBMITTED => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600',
                TrainingRequest::STATUS_UNDER_REVIEW => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700',
                TrainingRequest::STATUS_APPROVED => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700',
                TrainingRequest::STATUS_DECLINED => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700',
                TrainingRequest::STATUS_COMPLETED => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700',
            ],
        ]);
    }
}
