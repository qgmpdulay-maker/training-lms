<?php

use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EvaluationController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\SummaryController;
use App\Http\Controllers\Admin\SuperAdmin\UserManagementController;
use App\Http\Controllers\Admin\ToolsController;
use App\Http\Controllers\Admin\TrainingNeedsAssessmentController as AdminTrainingNeedsAssessmentController;
use App\Http\Controllers\Admin\TrainingRequestController as AdminTrainingRequestController;
use App\Http\Controllers\Participant\DashboardController;
use App\Http\Controllers\Participant\ProfileController;
use App\Http\Controllers\Participant\TrainingCatalogController;
use App\Http\Controllers\Participant\TrainingNeedsAssessmentController;
use App\Http\Controllers\Participant\TrainingRequestController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

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
});

Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/summary', [SummaryController::class, 'index'])->name('summary');
    Route::get('/summary/{trainingRequest}/edit', [SummaryController::class, 'edit'])->name('summary.edit');
    Route::patch('/summary/{trainingRequest}', [SummaryController::class, 'update'])->name('summary.update');
    Route::get('/tools', [ToolsController::class, 'index'])->name('tools');
    Route::post('/tools/{trainingRequest}/files', [ToolsController::class, 'uploadFiles'])->name('tools.files');
    Route::get('/evaluations/{trainingRequest}/edit', [EvaluationController::class, 'edit'])->name('evaluations.edit');
    Route::put('/evaluations/{trainingRequest}', [EvaluationController::class, 'update'])->name('evaluations.update');
    Route::get('/calendar', [AdminCalendarController::class, 'index'])->name('calendar');

    Route::get('/training-needs-assessment', [AdminTrainingNeedsAssessmentController::class, 'index'])->name('training-needs-assessment');

    Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
    Route::post('/instructors', [InstructorController::class, 'store'])->name('instructors.store');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/promote', [UserManagementController::class, 'promote'])->name('users.promote');
    Route::post('/users/{user}/demote', [UserManagementController::class, 'demote'])->name('users.demote');

    Route::get('/instructors/{instructor}', [InstructorController::class, 'show'])->name('instructors.show');
    Route::patch('/instructors/{instructor}/complaints', [InstructorController::class, 'updateComplaints'])->name('instructors.complaints');
});

// Regional admins only — requesting a training is a regional-office responsibility,
// not something Super Admin (Central) or participants do.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/training-requests/create', [AdminTrainingRequestController::class, 'create'])->name('training-requests.create');
    Route::post('/training-requests', [AdminTrainingRequestController::class, 'store'])->name('training-requests.store');
});

require __DIR__.'/auth.php';
