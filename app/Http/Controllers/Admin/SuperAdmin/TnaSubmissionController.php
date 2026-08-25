<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\TnaSubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TnaSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        $submissions = TnaSubmission::filteredBy($filters)
            ->latest('date_assessed')
            ->paginate(20)
            ->withQueryString();

        return view('admin.super-admin.tna-submissions.index', [
            'submissions' => $submissions,
            'filters' => $filters,
            'regions' => config('regions.list'),
            'agencyTypeLabels' => TnaSubmission::$agencyTypeLabels,
            'statusLabels' => TnaSubmission::$statusLabels,
        ]);
    }

    public function perOrganization(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        $submissions = TnaSubmission::filteredBy($filters)->get();

        $organizationBreakdown = $submissions
            ->groupBy(fn (TnaSubmission $tna) => $tna->organizationLabel())
            ->map(function ($rows, $organization) {
                return [
                    'organization' => $organization,
                    'agency_type' => $rows->first()->agencyTypeLabel(),
                    'regions' => $rows->pluck('region')->unique()->sort()->implode(', '),
                    'submissions' => $rows->count(),
                    'topics' => $rows->pluck('training_topic')->filter()->unique()->count(),
                    'personnel' => $rows->sum('personnel_assessed'),
                    'with_results' => $rows->filter(fn (TnaSubmission $tna) => $tna->hasResultsPdf())->count(),
                    'pending' => $rows->where('status', TnaSubmission::STATUS_PENDING)->count(),
                    'reviewed' => $rows->where('status', TnaSubmission::STATUS_REVIEWED)->count(),
                ];
            })
            ->sortByDesc('submissions')
            ->values();

        $topicBreakdown = $submissions
            ->groupBy('training_topic')
            ->map(fn ($rows, $topic) => [
                'topic' => $topic,
                'submissions' => $rows->count(),
                'organizations' => $rows->pluck('organization')->filter()->unique()->count(),
                'personnel' => $rows->sum('personnel_assessed'),
            ])
            ->sortByDesc('submissions')
            ->values();

        return view('admin.super-admin.tna-submissions.per-organization', [
            'filters' => $filters,
            'regions' => config('regions.list'),
            'agencyTypeLabels' => TnaSubmission::$agencyTypeLabels,
            'statusLabels' => TnaSubmission::$statusLabels,
            'summary' => [
                'submissions' => $submissions->count(),
                'organizations' => $submissions->pluck('organization')->filter()->unique()->count(),
                'topics' => $submissions->pluck('training_topic')->filter()->unique()->count(),
                'personnel' => $submissions->sum('personnel_assessed'),
                'with_results' => $submissions->filter(fn (TnaSubmission $tna) => $tna->hasResultsPdf())->count(),
                'reviewed' => $submissions->where('status', TnaSubmission::STATUS_REVIEWED)->count(),
            ],
            'organizationBreakdown' => $organizationBreakdown,
            'topicBreakdown' => $topicBreakdown,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        TnaSubmission::create($validated);

        return Redirect::route('admin.tna-submissions.index')->with('status', 'TNA submission recorded.');
    }

    public function update(Request $request, TnaSubmission $tnaSubmission): RedirectResponse
    {
        $validated = $this->validated($request);

        $tnaSubmission->update($validated);

        return Redirect::route('admin.tna-submissions.index')->with('status', 'TNA submission updated.');
    }

    public function uploadResults(Request $request, TnaSubmission $tnaSubmission): RedirectResponse
    {
        $validated = $request->validate([
            'results_pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $tnaSubmission->update([
            'results_pdf_path' => $validated['results_pdf']->store('tna-results', 'public'),
        ]);

        return Redirect::route('admin.tna-submissions.index')->with('status', 'Results PDF uploaded.');
    }

    public function downloadForm(): \Symfony\Component\HttpFoundation\Response
    {
        return Pdf::loadView('pdf.tna-submission-form')
            ->setPaper('a4', 'portrait')
            ->download('training-needs-assessment-form.pdf');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'region' => ['required', 'string', 'in:'.implode(',', config('regions.list'))],
            'agency_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(TnaSubmission::$agencyTypeLabels))],
            'organization' => ['nullable', 'string', 'max:255'],
            'training_topic' => ['required', 'string', 'max:255'],
            'personnel_assessed' => ['required', 'integer', 'min:0'],
            'date_assessed' => ['required', 'date'],
            'submitted_by' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(TnaSubmission::$statusLabels))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return array{regions?: array, agency_type?: string, status?: string, from?: string, until?: string}
     */
    private function filtersFromRequest(Request $request): array
    {
        return array_filter([
            'regions' => $request->query('regions', []),
            'agency_type' => $request->query('agency_type'),
            'status' => $request->query('status'),
            'from' => $request->query('from'),
            'until' => $request->query('until'),
        ]);
    }
}
