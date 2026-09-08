<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingNeedsAssessment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingNeedsAssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $region = $user->isSuperAdmin() ? $request->query('region') : null;
        $search = trim((string) $request->query('tna_q'));

        $regionScope = fn ($query) => $query
            ->when(
                $user->isAdmin(),
                fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('region', $user->region))
            )
            ->when(
                $region,
                fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('region', $region))
            );

        $submissions = TrainingNeedsAssessment::with('user')
            ->tap($regionScope)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('top_category', 'like', "%{$search}%")
                        ->orWhereRaw("DATE_FORMAT(created_at, '%b %d, %Y') like ?", ["%{$search}%"])
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%")
                                ->orWhere('organization', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'submissions_page')
            ->withQueryString()
            ->fragment('tna-submissions');

        // The submissions search box re-requests this same route and swaps in just
        // the results, so typing (or paging) doesn't reload the whole page — see
        // resources/views/admin/partials/live-search-script.blade.php.
        if ($request->ajax() && $request->query('_section') === 'tna-submissions') {
            return view('admin.partials.tna-submissions-results', [
                'submissions' => $submissions,
                'submissionSearch' => $search,
            ]);
        }

        return view('admin.training-needs-assessment', [
            'submissions' => $submissions,
            'submissionSearch' => $search,
            'regions' => config('regions.list'),
            'selectedRegion' => $region,
        ]);
    }
}
