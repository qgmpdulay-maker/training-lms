<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TnaSubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class TnaSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $region = $request->query('region');
            $filters = $this->filtersFromRequest($request);

            $submissions = TnaSubmission::filteredBy($filters)
                ->latest('date_assessed')
                ->paginate(20)
                ->withQueryString();

            $allMatching = TnaSubmission::filteredBy($filters)->get();

            return view('admin.super-admin.tna-submissions.index', [
                'submissions' => $submissions,
                'regions' => config('regions.list'),
                'selectedRegion' => $region,
                'agencyTypeLabels' => TnaSubmission::$agencyTypeLabels,
                'statusLabels' => TnaSubmission::$statusLabels,
                'filters' => $filters,
                'chartData' => $this->chartData($allMatching),
            ]);
        }

        // Regional admins only submit and manage records for their own region.
        $filters = ['regions' => [$user->region]];

        $submissions = TnaSubmission::filteredBy($filters)
            ->latest('date_assessed')
            ->paginate(20)
            ->withQueryString();

        return view('admin.tna-submissions', [
            'submissions' => $submissions,
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
        $validated['region'] = $request->user()->region;

        if ($request->hasFile('results_pdf')) {
            $validated['results_pdf_path'] = $request->file('results_pdf')->store('tna-results', 'public');
        }

        TnaSubmission::create($validated);

        return Redirect::route('admin.tna-submissions.index')->with('status', 'TNA submission recorded.');
    }

    public function update(Request $request, TnaSubmission $tnaSubmission): RedirectResponse
    {
        abort_if($tnaSubmission->region !== $request->user()->region, 403, 'You can only manage TNA submissions for your own region.');

        $tnaSubmission->update($this->validated($request));

        return Redirect::route('admin.tna-submissions.index')->with('status', 'TNA submission updated.');
    }

    public function uploadResults(Request $request, TnaSubmission $tnaSubmission): RedirectResponse
    {
        abort_if($tnaSubmission->region !== $request->user()->region, 403, 'You can only manage TNA submissions for your own region.');

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
            'agency_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(TnaSubmission::$agencyTypeLabels))],
            'organization' => ['nullable', 'string', 'max:255'],
            'training_topic' => ['required', 'string', 'max:255'],
            'personnel_assessed' => ['required', 'integer', 'min:0'],
            'date_assessed' => ['required', 'date'],
            'submitted_by' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(TnaSubmission::$statusLabels))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'results_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);
    }

    /**
     * @return array{regions?: array, agency_type?: string, status?: string, from?: string, until?: string}
     */
    private function filtersFromRequest(Request $request): array
    {
        $region = $request->query('region');

        return array_filter([
            'regions' => $region ? [$region] : [],
            'agency_type' => $request->query('agency_type'),
            'status' => $request->query('status'),
            'from' => $request->query('from'),
            'until' => $request->query('until'),
        ]);
    }

    /**
     * Region and review-status breakdowns for Super Admin's aggregate chart —
     * they observe trends across regions rather than filing submissions themselves.
     *
     * @param  Collection<int, TnaSubmission>  $submissions
     */
    private function chartData(Collection $submissions): array
    {
        $byRegion = collect(config('regions.list'))
            ->map(fn (string $region) => ['region' => $region, 'count' => $submissions->where('region', $region)->count()])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->all();

        return [
            'byRegion' => $byRegion,
            'byStatus' => [
                'pending' => $submissions->where('status', TnaSubmission::STATUS_PENDING)->count(),
                'reviewed' => $submissions->where('status', TnaSubmission::STATUS_REVIEWED)->count(),
            ],
        ];
    }
}
