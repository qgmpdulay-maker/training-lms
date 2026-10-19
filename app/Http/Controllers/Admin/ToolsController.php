<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AtarRecord;
use App\Models\TrainingRequest;
use App\Services\EvaluationSummary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ToolsController extends Controller
{
    public function __construct(private readonly EvaluationSummary $evaluationSummary)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $region = $user->isAdmin() ? $user->region : ($user->isSuperAdmin() ? $request->query('region') : null);

        $trainingSearch = trim((string) $request->query('trainings_q'));

        // The regions send their training data as a CSV for the Super Admin to
        // import; the count is just so the card can say what's already in.
        $importedRecordCount = $user->isSuperAdmin()
            ? AtarRecord::when($region, fn ($query) => $query->where('region', $region))->count()
            : 0;

        return view('admin.tools', [
            'region' => $region,
            'regionLocked' => $user->isAdmin(),
            'regions' => config('regions.list'),
            'importedRecordCount' => $importedRecordCount,
            'trainingSearch' => $trainingSearch,
            'evaluationsByTraining' => $this->evaluationSummaries($region, $trainingSearch),
        ]);
    }

    /**
     * One session's full L1/L2 breakdown, fetched when its row on the
     * Evaluation Computation list is first expanded.
     *
     * The Tools page used to render every session's full breakdown up front
     * (26 MB of HTML, ~90 MB of server memory with the demo data). It now
     * shows only the collapsed rows, and the browser calls this route for a
     * session the first time someone opens it (see the evaluationSession()
     * script at the bottom of admin/tools.blade.php). Returns just the HTML
     * fragment in admin/partials/evaluation-session-details.blade.php.
     */
    public function evaluationDetails(Request $request, TrainingRequest $trainingRequest): View
    {
        // Regional Admins may only open sessions from their own region —
        // the same rule as every other Tools action.
        $user = $request->user();
        abort_if($user->isAdmin() && $trainingRequest->region !== $user->region, 403);

        // Everything the breakdown needs for this one session, in a few queries.
        $trainingRequest->load(['trainingEvaluation', 'participantEvaluations.user:id,name', 'instructors'])
            ->loadCount('participants');

        return view('admin.partials.evaluation-session-details', [
            'session' => $this->evaluationSummary->forSession($trainingRequest),
        ]);
    }

    /**
     * Blank Training Needs Assessment for agencies that can't (or would rather
     * not) fill it in online — print, complete by hand, return to the Regional
     * Office. Deliberately generic: the real per-LGU templates are still to
     * come from CDTI, so this mirrors the online form's sections rather than
     * inventing content for participant types that aren't mapped yet.
     */
    public function downloadTnaTemplate(): Response
    {
        return Pdf::loadView('pdf.tna-template')
            ->setPaper('a4', 'portrait')
            ->download('training-needs-assessment-form.pdf');
    }

    public function downloadCertificateTemplate(): Response
    {
        return Pdf::loadView('pdf.certificate-template')
            ->setPaper('a4', 'landscape')
            ->download('training-certificate-template.pdf');
    }

    /**
     * Every evaluated session, grouped by training title.
     *
     * All titles are listed (the left-hand list scrolls); it's the sessions
     * underneath a title that are paged, ten at a time, in the view.
     * The Tools page groups by title (rather than pooling sessions of the same
     * training into shared stats) because every session gets exactly one
     * admin-entered evaluation via "Add Evaluation", so pooling mixed
     * unrelated runs' pre/post-test scores into meaningless combined figures.
     * A session appears here if it has an admin evaluation, one or more
     * participant evaluations, or both — module and instructor ratings are
     * shown side by side from each source rather than merged into one number,
     * since they measure different things (the admin's own assessment vs.
     * what participants reported).
     *
     * @return array<string, Collection>
     */
    private function evaluationSummaries(?string $region, string $search): array
    {
        return TrainingRequest::where(fn ($query) => $query->whereHas('trainingEvaluation')->orWhereHas('participantEvaluations'))
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when($search !== '', fn ($query) => $query->where('training_title', 'like', "%{$search}%"))
            // Only what the collapsed session rows show — each session's full
            // breakdown is built on demand by evaluationDetails(). Evaluations
            // load just the columns the row needs, and participants are only
            // counted (in SQL) rather than loaded.
            ->with(['trainingEvaluation', 'participantEvaluations:id,training_request_id,module_ratings,updated_at'])
            ->withCount('participants')
            ->orderByDesc('preferred_date')
            ->get()
            ->map(fn (TrainingRequest $trainingRequest) => $this->evaluationSummary->header($trainingRequest))
            ->groupBy('training_title')
            ->sortKeys()
            ->all();
    }
}
