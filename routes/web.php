<?php

use App\Http\Controllers\Admin\AtarReportController;
use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EvaluationController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\ParticipantDeploymentController;
use App\Http\Controllers\Admin\SummaryController;
use App\Http\Controllers\Admin\TnaFormController;
use App\Http\Controllers\Admin\SuperAdmin\AtarRecordController;
use App\Http\Controllers\Admin\SuperAdmin\OrganizationController;
use App\Http\Controllers\Admin\SuperAdmin\UserManagementController;
use App\Http\Controllers\Admin\ToolsController;
use App\Http\Controllers\Admin\TrainingController as AdminTrainingController;
use App\Http\Controllers\Admin\TrainingNeedsAssessmentController as AdminTrainingNeedsAssessmentController;
use App\Http\Controllers\Participant\CertificateController;
use App\Http\Controllers\Participant\DashboardController;
use App\Http\Controllers\Participant\EvaluationController as ParticipantEvaluationController;
use App\Http\Controllers\Participant\ProfileController;
use App\Http\Controllers\Participant\TrainingCatalogController;
use App\Http\Controllers\Participant\TrainingNeedsAssessmentController;
use App\Http\Controllers\Participant\TrainingRequestController;
use App\Http\Controllers\PublicTrainingCatalogController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicTrainingCatalogController::class, 'index'])->name('home');
Route::view('/about', 'public.about')->name('about');

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

// Regional Admins see their region's data and change almost none of it. The
// exceptions: their graduates' deployments and city (the deployments routes
// below), and authoring their region's ATAR (the role:admin group at the
// bottom of this file). Every other training and certification write lives
// in the Super Admin group further down.
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/summary', [SummaryController::class, 'index'])->name('summary');
    Route::get('/summary/{trainingRequest}/edit', [SummaryController::class, 'edit'])->name('summary.edit');
    // Read-only: the evaluation results for one training, which is where the
    // Summary page's Evaluations card lands. Nothing on this page writes —
    // editing an evaluation is the separate evaluations.update route below.
    Route::get('/summary/{trainingRequest}/evaluations', [SummaryController::class, 'evaluations'])->name('summary.evaluations');
    Route::get('/tools', [ToolsController::class, 'index'])->name('tools');
    // Returns one session's L1/L2 evaluation breakdown as an HTML fragment.
    // The Tools page fetches it when a session row is first expanded, instead
    // of rendering every session's breakdown up front.
    Route::get('/tools/evaluations/{trainingRequest}', [ToolsController::class, 'evaluationDetails'])->name('tools.evaluation');
    Route::get('/tools/tna-template', [ToolsController::class, 'downloadTnaTemplate'])->name('tools.tna-template');
    Route::get('/tools/certificate-template', [ToolsController::class, 'downloadCertificateTemplate'])->name('tools.certificate-template');
    Route::get('/evaluations/{trainingRequest}/edit', [EvaluationController::class, 'edit'])->name('evaluations.edit');
    Route::get('/calendar', [AdminCalendarController::class, 'index'])->name('calendar');

    Route::get('/training-needs-assessment', [AdminTrainingNeedsAssessmentController::class, 'index'])->name('training-needs-assessment');

    // The instructor roster. Regional staff know who in their region can
    // teach what, so they maintain their own roster — adding instructors and
    // recording each one's courses, certifications, and deployments. The
    // controller scopes every one of these to the admin's own region; only
    // moving an instructor between regions stays Super Admin (further down).
    Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
    Route::get('/instructors/{instructor}', [InstructorController::class, 'show'])->name('instructors.show');
    Route::post('/instructors', [InstructorController::class, 'store'])->name('instructors.store');
    Route::post('/instructors/{instructor}/photo', [InstructorController::class, 'uploadPhoto'])->name('instructors.photo');

    // Courses, certifications, and deployments are many-per-instructor, so
    // each is added and removed a row at a time rather than edited in place.
    Route::post('/instructors/{instructor}/training-types', [InstructorController::class, 'addTrainingType'])->name('instructors.training-types.store');
    Route::delete('/instructor-training-types/{trainingType}', [InstructorController::class, 'removeTrainingType'])->name('instructors.training-types.destroy');
    Route::post('/instructors/{instructor}/certifications', [InstructorController::class, 'addCertification'])->name('instructors.certifications.store');
    Route::delete('/instructor-certifications/{certification}', [InstructorController::class, 'removeCertification'])->name('instructors.certifications.destroy');
    Route::post('/instructors/{instructor}/deployments', [InstructorController::class, 'addDeployment'])->name('instructors.deployments.store');
    Route::patch('/instructor-deployments/{deployment}', [InstructorController::class, 'updateDeployment'])->name('instructors.deployments.update');
    Route::delete('/instructor-deployments/{deployment}', [InstructorController::class, 'removeDeployment'])->name('instructors.deployments.destroy');

    // What's scheduled: a Regional Admin's own region, every region for a
    // Super Admin. Scheduling itself is in the role:admin group at the bottom
    // of this file.
    Route::get('/trainings', [AdminTrainingController::class, 'index'])->name('trainings.index');

    // Regional staff know which of their graduates got sent to an operation,
    // and which city each is based in for the Graduates by Location map, so
    // they maintain both for their own region; the controller scopes them to
    // it. Graduates only — the controller 404s for anyone who hasn't
    // completed a training.
    Route::get('/deployments', [ParticipantDeploymentController::class, 'index'])->name('deployments.index');
    Route::post('/deployments/{user}', [ParticipantDeploymentController::class, 'store'])->name('deployments.store');
    Route::post('/deployments/{user}/city', [ParticipantDeploymentController::class, 'updateCity'])->name('deployments.city');
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
    // Staff transfer between regional offices. Every admin screen scopes off
    // users.region, so this is what hands a Regional Admin their new
    // region's data and takes away their old one's.
    Route::patch('/users/{user}/region', [UserManagementController::class, 'updateRegion'])->name('users.region');
    Route::post('/users/{user}/promote', [UserManagementController::class, 'promote'])->name('users.promote');
    Route::post('/users/{user}/demote', [UserManagementController::class, 'demote'])->name('users.demote');
    Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/reset-passwords', [UserManagementController::class, 'bulkResetPassword'])->name('users.reset-passwords');

    // Same transfer, for the instructor roster — instructors get posted to
    // another regional office, or sent to run a training outside their own.
    // Super Admin only: a Regional Admin can't post one out of, or into,
    // their own region. The rest of the roster is in the shared group above.
    Route::patch('/instructors/{instructor}/region', [InstructorController::class, 'updateRegion'])->name('instructors.region');

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
    // certificate), and editing evaluations are all Super Admin decisions — a
    // Regional Admin views their region's training data and changes none of it.
    Route::patch('/summary/{trainingRequest}', [SummaryController::class, 'update'])->name('summary.update');
    Route::put('/evaluations/{trainingRequest}', [EvaluationController::class, 'update'])->name('evaluations.update');

    // Editing the Training Needs Assessment questionnaires themselves — the
    // questions each participant type is asked, and the scoring their options
    // carry. Reached from the Needs Assessment page.
    Route::get('/tna-forms', [TnaFormController::class, 'index'])->name('tna-forms.index');
    Route::get('/tna-forms/create', [TnaFormController::class, 'create'])->name('tna-forms.create');
    Route::post('/tna-forms', [TnaFormController::class, 'store'])->name('tna-forms.store');
    Route::get('/tna-forms/{tnaForm}/edit', [TnaFormController::class, 'edit'])->name('tna-forms.edit');
    Route::put('/tna-forms/{tnaForm}', [TnaFormController::class, 'update'])->name('tna-forms.update');
    Route::delete('/tna-forms/{tnaForm}', [TnaFormController::class, 'destroy'])->name('tna-forms.destroy');
    Route::post('/tna-forms/{tnaForm}/questions', [TnaFormController::class, 'storeQuestion'])->name('tna-forms.questions.store');
    Route::put('/tna-questions/{tnaQuestion}', [TnaFormController::class, 'updateQuestion'])->name('tna-questions.update');
    Route::delete('/tna-questions/{tnaQuestion}', [TnaFormController::class, 'destroyQuestion'])->name('tna-questions.destroy');
    Route::post('/tna-questions/{tnaQuestion}/move', [TnaFormController::class, 'moveQuestion'])->name('tna-questions.move');

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
    // the CSV-imported tracker rows. Only deleting one is Super Admin's;
    // writing and editing them live in the two groups further down.
    Route::delete('/atar-reports/{atarReport}', [AtarReportController::class, 'destroy'])->name('atar-reports.destroy');
});

// Reading and editing an ATAR: both admins, each scoped by the controller —
// a Super Admin sees every region's report, a Regional Admin only their own.
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/atar-reports', [AtarReportController::class, 'index'])->name('atar-reports.index');
    Route::get('/atar-reports/participants/search', [AtarReportController::class, 'searchParticipants'])->name('atar-reports.participants.search');
    Route::get('/atar-reports/{atarReport}/edit', [AtarReportController::class, 'edit'])->name('atar-reports.edit');
    Route::put('/atar-reports/{atarReport}', [AtarReportController::class, 'update'])->name('atar-reports.update');
    Route::get('/atar-reports/{atarReport}/participants/{user}', [AtarReportController::class, 'participantDetails'])->name('atar-reports.participants.show');
    Route::post('/atar-reports/{atarReport}/recompute', [AtarReportController::class, 'recompute'])->name('atar-reports.recompute');
    Route::get('/atar-reports/{atarReport}/pdf', [AtarReportController::class, 'pdf'])->name('atar-reports.pdf');
});

// Writing a new ATAR is the Regional Admin's alone — role:admin excludes the
// Super Admin on purpose. The ATAR is the region's own account of a training
// it ran, so the region authors it; the Super Admin reviews and edits after.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/atar-reports/create', [AtarReportController::class, 'create'])->name('atar-reports.create');
    Route::post('/atar-reports', [AtarReportController::class, 'store'])->name('atar-reports.store');

    // Scheduling a training. The Regional Admin is already authorised to run
    // trainings in their region, so this has no approval step — what they
    // save is scheduled. The Super Admin views and edits, never creates.
    Route::get('/trainings/create', [AdminTrainingController::class, 'create'])->name('trainings.create');
    Route::post('/trainings', [AdminTrainingController::class, 'store'])->name('trainings.store');
    Route::get('/trainings/participants', [AdminTrainingController::class, 'participants'])->name('trainings.participants');
});

require __DIR__.'/auth.php';
