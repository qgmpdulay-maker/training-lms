<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SummaryController extends Controller
{
    // Same colors used on the Calendar / Tools status chart, so a status means
    // the same thing everywhere an admin sees it.
    const STATUS_COLORS = [
        TrainingRequest::STATUS_SUBMITTED => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600',
        TrainingRequest::STATUS_UNDER_REVIEW => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700',
        TrainingRequest::STATUS_APPROVED => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700',
        TrainingRequest::STATUS_DECLINED => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700',
        TrainingRequest::STATUS_COMPLETED => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700',
    ];

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $records = TrainingRequest::with(['user', 'participants'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('preferred_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.summary', [
            'records' => $records,
            'statusLabels' => TrainingRequest::$statusLabels,
            'statusColors' => self::STATUS_COLORS,
            'selectedStatus' => $status,
        ]);
    }

    public function edit(TrainingRequest $trainingRequest): View
    {
        $trainingRequest->load('participants');

        return view('admin.summary-edit', [
            'record' => $trainingRequest,
            'participants' => $trainingRequest->effectiveParticipants(),
            'statusLabels' => TrainingRequest::$statusLabels,
            'certificateRemarksLabels' => TrainingRequest::$certificateRemarksLabels,
            'categoryLabels' => TrainingRequest::$categoryLabels,
            'regions' => config('regions.list'),
        ]);
    }

    public function update(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(TrainingRequest::$statusLabels))],
            'preferred_date' => ['required', 'date'],
            'venue' => ['required', 'string', 'max:255'],
            'lgu' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'in:'.implode(',', config('regions.list'))],
            'category' => ['nullable', 'string', 'in:'.implode(',', array_keys(TrainingRequest::$categoryLabels))],
            'certificate_code' => ['nullable', 'string', 'max:255'],
            'certificate_remarks' => ['nullable', 'string', 'in:'.implode(',', array_keys(TrainingRequest::$certificateRemarksLabels))],
        ]);

        // Regional admins can only tag training requests as belonging to their own region.
        if ($request->user()->isAdmin()) {
            $validated['region'] = $request->user()->region;
        }

        $trainingRequest->update($validated);

        return Redirect::route('admin.summary')->with('status', "Changes saved for {$trainingRequest->training_title}.");
    }
}
