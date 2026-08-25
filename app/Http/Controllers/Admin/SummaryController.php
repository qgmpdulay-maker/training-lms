<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\TrainingRequest;
use App\Models\User;
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
        $user = $request->user();
        $statusParam = $request->query('status');
        // No status chosen yet: prioritize newly received requests over ones
        // already being reviewed, approved, completed, or declined.
        $status = $statusParam ?? TrainingRequest::STATUS_SUBMITTED;
        $statusDefaulted = $statusParam === null;
        $showAllStatuses = $status === 'all';
        $search = trim((string) $request->query('q'));
        $region = $user->isSuperAdmin() ? $request->query('region') : null;

        $records = TrainingRequest::with(['user', 'participants'])
            ->when($user->isAdmin(), fn ($query) => $query->where('region', $user->region))
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when(! $showAllStatuses, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('venue', 'like', "%{$search}%")
                        ->orWhere('training_title', 'like', "%{$search}%")
                        ->orWhere('requesting_agency', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhereRaw("DATE_FORMAT(preferred_date, '%b %d, %Y') like ?", ["%{$search}%"])
                        ->orWhereHas('participants', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('preferred_date')
            ->paginate(10)
            ->withQueryString()
            ->fragment('training-requests');

        $participantSearch = trim((string) $request->query('participants_q'));

        $participants = ($user->isAdmin() || $user->isSuperAdmin())
            ? User::where('role', User::ROLE_PARTICIPANT)
                ->when($user->isAdmin(), fn ($query) => $query->where('region', $user->region))
                ->when($region, fn ($query) => $query->where('region', $region))
                ->when($participantSearch !== '', function ($query) use ($participantSearch) {
                    $query->where(function ($q) use ($participantSearch) {
                        $q->where('name', 'like', "%{$participantSearch}%")
                            ->orWhere('email', 'like', "%{$participantSearch}%")
                            ->orWhere('organization', 'like', "%{$participantSearch}%")
                            ->orWhere('agency', 'like', "%{$participantSearch}%")
                            ->orWhere('participant_type', 'like', "%{$participantSearch}%")
                            ->orWhere('mobile_number', 'like', "%{$participantSearch}%");
                    });
                })
                ->orderBy('name')
                ->paginate(10, ['*'], 'participants')
                ->withQueryString()
                ->fragment('registered-participants')
            : null;

        $instructorSearch = trim((string) $request->query('instructors_q'));

        $instructors = $user->isSuperAdmin()
            ? Instructor::when($region, fn ($query) => $query->where('region', $region))
                ->when($instructorSearch !== '', function ($query) use ($instructorSearch) {
                    $query->where(function ($q) use ($instructorSearch) {
                        $q->where('name', 'like', "%{$instructorSearch}%")
                            ->orWhere('training_type', 'like', "%{$instructorSearch}%")
                            ->orWhere('specialization', 'like', "%{$instructorSearch}%")
                            ->orWhere('agency_organization', 'like', "%{$instructorSearch}%")
                            ->orWhere('lgu', 'like', "%{$instructorSearch}%")
                            ->orWhere('certificate_code', 'like', "%{$instructorSearch}%");
                    });
                })
                ->orderBy('name')
                ->paginate(10, ['*'], 'instructors')
                ->withQueryString()
                ->fragment('instructors')
            : null;

        return view('admin.summary', [
            'records' => $records,
            'statusLabels' => TrainingRequest::$statusLabels,
            'statusColors' => self::STATUS_COLORS,
            'selectedStatus' => $status,
            'statusDefaulted' => $statusDefaulted,
            'search' => $search,
            'participants' => $participants,
            'participantSearch' => $participantSearch,
            'instructors' => $instructors,
            'instructorSearch' => $instructorSearch,
            'regions' => config('regions.list'),
            'selectedRegion' => $region,
        ]);
    }

    public function edit(Request $request, TrainingRequest $trainingRequest): View
    {
        // Regional admins may only manage training requests tagged to their own region.
        abort_if($request->user()->isAdmin() && $trainingRequest->region !== $request->user()->region, 403);

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
        abort_if($request->user()->isAdmin() && $trainingRequest->region !== $request->user()->region, 403);

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
