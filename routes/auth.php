<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\OtpVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    // Rate-limited (limits defined in AppServiceProvider): every registration
    // emails a code, so unlimited submissions could flood someone's inbox or
    // use up the Gmail sending quota.
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:registration');

    Route::get('verify-otp', [OtpVerificationController::class, 'show'])
        ->name('otp.show');

    Route::post('verify-otp', [OtpVerificationController::class, 'store'])
        ->name('otp.verify');

    Route::post('verify-otp/resend', [OtpVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('otp.resend');

    Route::view('registration-pending', 'auth.registration-pending')
        ->name('registration.pending');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // Rate-limited per IP across all emails (limit defined in
    // AppServiceProvider); LoginRequest separately locks each email+IP pair.
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    // Throttled so someone holding a stolen session can't guess the
    // account's password here unchecked.
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
