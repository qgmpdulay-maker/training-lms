<?php

use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EvaluationController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\ParticipantDeploymentController;
use App\Http\Controllers\Admin\SummaryController;
use App\Http\Controllers\Admin\SuperAdmin\AtarRecordController;
use App\Http\Controllers\Admin\SuperAdmin\AtarReportController;
use App\Http\Controllers\Admin\SuperAdmin\MonitoringController;
use App\Http\Controllers\Admin\SuperAdmin\OrganizationController;
use App\Http\Controllers\Admin\SuperAdmin\TrainingController as SuperAdminTrainingController;
use App\Http\Controllers\Admin\SuperAdmin\UserManagementController;
use App\Http\Controllers\Admin\ToolsController;
use App\Http\Controllers\Admin\TrainingNeedsAssessmentController as AdminTrainingNeedsAssessmentController;
use App\Http\Controllers\Participant\CertificateController;
use App\Http\Controllers\Participant\DashboardController;
use App\Http\Controllers\Participant\EvaluationController as ParticipantEvaluationController;
use App\Http\Controllers\Participant\ProfileController;
use App\Http\Controllers\Participant\TrainingCatalogController;
use App\Http\Controllers\Participant\TrainingNeedsAssessmentController;
use App\Http\Controllers\Participant\TrainingRequestController;
use App\Http\Controllers\PublicTrainingCatalogController;
use App\Http\Controllers\PublicTrainingRequestController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicTrainingCatalogController::class, 'index'])->name('home');
Route::view('/about', 'public.about')->name('about');

// Public, unauthenticated Technical Assistance request portal — the requester
// is the Agency/LGU itself, so there's no login, no account, and no auth
// middleware here. See PublicTrainingRequestController for the full flow.
// The POST is throttled because it's open to the internet: 5 submissions per
// minute per IP, enough for a real agency retrying a failed upload and far
// too few to flood the review queue.
Route::get('/request-training', [PublicTrainingRequestController::class, 'create'])->name('public.training-requests.create');
Route::post('/request-training', [PublicTrainingRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('public.training-requests.store');
Route::get('/request-training/submitted', [PublicTrainingRequestController::class, 'submitted'])->name('public.training-requests.submitted');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/id-card', [ProfileController::class, 'idCard'])->name('profile.id-card');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/trainings', [TrainingCatalogController::class, 'index'])->name('trainings.index');

    Route::get('/training-needs-assessment', [TrainingNeedsAssessmentController::class, 'index'])->name('training-needs-assessment.index');
    Route::post('/training-needs-assessment/recommendation', [TrainingNeedsAssessmentController::class, 'storeRecommendation'])->name('training-needs-assessment.recommendation');
    Route::post('/training-needs-assessment/academe', [TrainingNeedsAssessmentController::class, 'storeAcademe'])->name('training-needs-assessment.academe.store');
    Route::post('/training-needs-assessment/nrdrrmc', [TrainingNeedsAssessmentController::class, 'storeNrdrrmc'])->name('training-needs-assessment.nrdrrmc.store');
    Route::post('/training-needs-assessment/lgu', [TrainingNeedsAssessmentController::class, 'storeLgu'])->name('training-needs-assessment.lgu.store');
    Route::post('/training-needs-assessment/volunteer', [TrainingNeedsAssessmentController::class, 'storeVolunteer'])->name('training-needs-assessment.volunteer.store');

    Route::get('/training-requests', [TrainingRequestController::class, 'index'])->name('training-requests.index');
    Route::get('/training-requests/{trainingRequest}', [TrainingRequestController::class, 'show'])->name('training-requests.show');
    Route::get('/training-requests/{trainingRequest}/evaluation', [ParticipantEvaluationController::class, 'edit'])->name('training-requests.evaluation.edit');
    Route::put('/training-requests/{trainingRequest}/evaluation', [ParticipantEvaluationController::class, 'update'])->name('training-requests.evaluation.update');

    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    // Opens one certificate PDF. Certificates are private files, so every
    // certificate link goes through here, and the controller checks the
    // viewer is allowed to see it (owner, Super Admin, or that region's admin).
    Route::get('/certificates/{certificate}', [CertificateController::class, 'download'])->name('certificates.download');
});

// Regional Admins see their region's data and change none of it, with a
// single exception: recording where their graduates have been deployed
// (the deployments routes below). Every other training and certification
// write lives in the Super Admin group further down.
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/summary', [SummaryController::class, 'index'])->name('summary');
    Route::get('/summary/{trainingRequest}/edit', [SummaryController::class, 'edit'])->name('summary.edit');
    // Opens the TNA or signed request letter a public requester uploaded.
    // Those files are private (storage/app/private), so they're served here
    // with a region check rather than linked straight from /storage.
    Route::get('/summary/{trainingRequest}/attachment/{type}', [SummaryController::class, 'attachment'])->name('summary.attachment');
    Route::get('/tools', [ToolsController::class, 'index'])->name('tools');
    // Returns one session's L1/L2 evaluation breakdown as an HTML fragment.
    // The Tools page fetches it when a session row is first expanded, instead
    // of rendering every session's breakdown up front.
    Route::get('/tools/evaluations/{trainingRequest}', [ToolsController::class, 'evaluationDetails'])->name('tools.evaluation');
    Route::get('/tools/atar-template', [ToolsController::class, 'downloadAtarTemplate'])->name('tools.atar-template');
    Route::get('/tools/certificate-template', [ToolsController::class, 'downloadCertificateTemplate'])->name('tools.certificate-template');
    Route::get('/evaluations/{trainingRequest}/edit', [EvaluationController::class, 'edit'])->name('evaluations.edit');
    Route::get('/calendar', [AdminCalendarController::class, 'index'])->name('calendar');

    Route::get('/training-needs-assessment', [AdminTrainingNeedsAssessmentController::class, 'index'])->name('training-needs-assessment');

    Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');

    // Graduates Map is Regional-Admin-reachable too — MonitoringController::map()
    // force-scopes a Regional Admin to their own region.
    Route::get('/monitoring/map', [MonitoringController::class, 'map'])->name('monitoring.map');

    // The Regional Admin's one write. Regional staff know which of their
    // graduates got sent to an operation, so they maintain this for their own
    // region; the controller scopes them to it. Graduates only — the
    // controller 404s for anyone who hasn't completed a training.
    Route::get('/deployments', [ParticipantDeploymentController::class, 'index'])->name('deployments.index');
    Route::post('/deployments/{user}', [ParticipantDeploymentController::class, 'store'])->name('deployments.store');
    Route::delete('/deployments/{deployment}', [ParticipantDeploymentController::class, 'destroy'])->name('deployments.destroy');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/registrations/{registration}/approve', [UserManagementController::class, 'approve'])->name('users.approve');
    Route::post('/registrations/{registration}/reject', [UserManagementController::class, 'reject'])->name('users.reject');
    // Placing an approved user into an LGU / NGA / academe body / team. This
    // is the membership that later lets them act for that body, which is why
    // only a Super Admin can set it.
    Route::post('/users/{user}/organization', [UserManagementController::class, 'assignOrganization'])->name('users.assign-organization');
    Route::post('/users/{user}/promote', [UserManagementController::class, 'promote'])->name('users.promote');
    Route::post('/users/{user}/demote', [UserManagementController::class, 'demote'])->name('users.demote');
    Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/reset-passwords', [UserManagementController::class, 'bulkResetPassword'])->name('users.reset-passwords');

    Route::get('/instructors/{instructor}', [InstructorController::class, 'show'])->name('instructors.show');

    // The LGUs / NGAs / academe bodies / teams OCD trains, and who belongs to
    // each. Curated here only — membership is what lets a member act for their
    // body, so nobody self-joins.
    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::patch('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
    Route::post('/organizations/{organization}/members', [OrganizationController::class, 'addMembers'])->name('organizations.members.add');
    Route::delete('/organizations/{organization}/members/{user}', [OrganizationController::class, 'removeMember'])->name('organizations.members.remove');

    // Writes that used to sit in the shared admin group above. Approving a
    // request, marking one Completed (which issues every participant's
    // certificate), editing evaluations, uploading training files, and
    // managing instructors are all Super Admin decisions — a Regional Admin
    // views their region's data and changes none of it.
    Route::patch('/summary/{trainingRequest}', [SummaryController::class, 'update'])->name('summary.update');
    Route::post('/tools/{trainingRequest}/files', [ToolsController::class, 'uploadFiles'])->name('tools.files');
    Route::put('/evaluations/{trainingRequest}', [EvaluationController::class, 'update'])->name('evaluations.update');
    Route::post('/instructors', [InstructorController::class, 'store'])->name('instructors.store');
    Route::patch('/instructors/{instructor}', [InstructorController::class, 'update'])->name('instructors.update');
    Route::post('/instructors/{instructor}/certificate', [InstructorController::class, 'uploadCertificate'])->name('instructors.certificate');
    Route::post('/instructors/{instructor}/photo', [InstructorController::class, 'uploadPhoto'])->name('instructors.photo');

    Route::get('/trainings/create', [SuperAdminTrainingController::class, 'create'])->name('trainings.create');
    Route::post('/trainings', [SuperAdminTrainingController::class, 'store'])->name('trainings.store');
    Route::get('/trainings/participants', [SuperAdminTrainingController::class, 'participants'])->name('trainings.participants');

    Route::get('/atar-records', [AtarRecordController::class, 'index'])->name('atar-records.index');
    Route::get('/atar-records/import', [AtarRecordController::class, 'create'])->name('atar-records.import');
    Route::get('/atar-records/import/template', [AtarRecordController::class, 'downloadTemplate'])->name('atar-records.import.template');
    Route::post('/atar-records/import', [AtarRecordController::class, 'store'])->name('atar-records.import.store');
    Route::post('/atar-records/import/confirm', [AtarRecordController::class, 'confirmImport'])->name('atar-records.import.confirm');
    Route::post('/atar-records/import/cancel', [AtarRecordController::class, 'cancelImport'])->name('atar-records.import.cancel');
    Route::delete('/atar-records/{atarRecord}', [AtarRecordController::class, 'destroy'])->name('atar-records.destroy');

    // Narrative ATAR reports (AtarReportController) — a separate feature
    // from atar-records above: these are the actual multi-page ATAR
    // documents (generated from a training or written from scratch), not
    // the CSV-imported tracker rows.
    Route::get('/atar-reports', [AtarReportController::class, 'index'])->name('atar-reports.index');
    Route::get('/atar-reports/create', [AtarReportController::class, 'create'])->name('atar-reports.create');
    Route::post('/atar-reports', [AtarReportController::class, 'store'])->name('atar-reports.store');
    Route::get('/atar-reports/participants/search', [AtarReportController::class, 'searchParticipants'])->name('atar-reports.participants.search');
    Route::get('/atar-reports/{atarReport}/edit', [AtarReportController::class, 'edit'])->name('atar-reports.edit');
    Route::put('/atar-reports/{atarReport}', [AtarReportController::class, 'update'])->name('atar-reports.update');
    Route::get('/atar-reports/{atarReport}/participants/{user}', [AtarReportController::class, 'participantDetails'])->name('atar-reports.participants.show');
    Route::post('/atar-reports/{atarReport}/recompute', [AtarReportController::class, 'recompute'])->name('atar-reports.recompute');
    Route::get('/atar-reports/{atarReport}/pdf', [AtarReportController::class, 'pdf'])->name('atar-reports.pdf');
    Route::delete('/atar-reports/{atarReport}', [AtarReportController::class, 'destroy'])->name('atar-reports.destroy');
});

require __DIR__.'/auth.php';
