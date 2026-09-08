<?php

use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EvaluationController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\SummaryController;
use App\Http\Controllers\Admin\SuperAdmin\MonitoringController;
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
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicTrainingCatalogController::class, 'index'])->name('home');

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

    Route::get('/training-requests', [TrainingRequestController::class, 'index'])->name('training-requests.index');
    Route::get('/training-requests/{trainingRequest}', [TrainingRequestController::class, 'show'])->name('training-requests.show');
    Route::get('/training-requests/{trainingRequest}/evaluation', [ParticipantEvaluationController::class, 'edit'])->name('training-requests.evaluation.edit');
    Route::put('/training-requests/{trainingRequest}/evaluation', [ParticipantEvaluationController::class, 'update'])->name('training-requests.evaluation.update');

    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
});

Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/summary', [SummaryController::class, 'index'])->name('summary');
    Route::get('/summary/{trainingRequest}/edit', [SummaryController::class, 'edit'])->name('summary.edit');
    Route::patch('/summary/{trainingRequest}', [SummaryController::class, 'update'])->name('summary.update');
    Route::get('/tools', [ToolsController::class, 'index'])->name('tools');
    Route::post('/tools/{trainingRequest}/files', [ToolsController::class, 'uploadFiles'])->name('tools.files');
    Route::get('/tools/atar-template', [ToolsController::class, 'downloadAtarTemplate'])->name('tools.atar-template');
    Route::get('/tools/certificate-template', [ToolsController::class, 'downloadCertificateTemplate'])->name('tools.certificate-template');
    Route::get('/evaluations/{trainingRequest}/edit', [EvaluationController::class, 'edit'])->name('evaluations.edit');
    Route::put('/evaluations/{trainingRequest}', [EvaluationController::class, 'update'])->name('evaluations.update');
    Route::get('/calendar', [AdminCalendarController::class, 'index'])->name('calendar');

    Route::get('/training-needs-assessment', [AdminTrainingNeedsAssessmentController::class, 'index'])->name('training-needs-assessment');

    Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
    Route::post('/instructors', [InstructorController::class, 'store'])->name('instructors.store');
    Route::patch('/instructors/{instructor}', [InstructorController::class, 'update'])->name('instructors.update');
    Route::post('/instructors/{instructor}/certificate', [InstructorController::class, 'uploadCertificate'])->name('instructors.certificate');
    Route::post('/instructors/{instructor}/photo', [InstructorController::class, 'uploadPhoto'])->name('instructors.photo');

    // Graduates Map is Regional-Admin-reachable too — MonitoringController::map()
    // force-scopes a Regional Admin to their own region.
    Route::get('/monitoring/map', [MonitoringController::class, 'map'])->name('monitoring.map');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/registrations/{registration}/approve', [UserManagementController::class, 'approve'])->name('users.approve');
    Route::post('/registrations/{registration}/reject', [UserManagementController::class, 'reject'])->name('users.reject');
    Route::post('/users/{user}/promote', [UserManagementController::class, 'promote'])->name('users.promote');
    Route::post('/users/{user}/demote', [UserManagementController::class, 'demote'])->name('users.demote');
    Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/reset-passwords', [UserManagementController::class, 'bulkResetPassword'])->name('users.reset-passwords');

    Route::get('/instructors/{instructor}', [InstructorController::class, 'show'])->name('instructors.show');

    Route::get('/trainings/create', [SuperAdminTrainingController::class, 'create'])->name('trainings.create');
    Route::post('/trainings', [SuperAdminTrainingController::class, 'store'])->name('trainings.store');
    Route::get('/trainings/participants', [SuperAdminTrainingController::class, 'participants'])->name('trainings.participants');

    Route::delete('/calendar-events/{calendarEvent}', [AdminCalendarController::class, 'destroy'])->name('calendar-events.destroy');
});

require __DIR__.'/auth.php';
