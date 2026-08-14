<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SummaryController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $records = TrainingRequest::with('user')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('preferred_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.summary', [
            'records' => $records,
            'statusLabels' => TrainingRequest::$statusLabels,
            'certificateRemarksLabels' => TrainingRequest::$certificateRemarksLabels,
            'selectedStatus' => $status,
        ]);
    }

    public function update(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $validated = $request->validate([
            'lgu' => ['nullable', 'string', 'max:255'],
            'certificate_code' => ['nullable', 'string', 'max:255'],
            'certificate_remarks' => ['nullable', 'string', 'in:'.implode(',', array_keys(TrainingRequest::$certificateRemarksLabels))],
        ]);

        $trainingRequest->update($validated);

        return back()->with('status', "Certificate details updated for {$trainingRequest->user->name}.");
    }
}
