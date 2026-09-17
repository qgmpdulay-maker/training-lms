<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AccountPendingApproval;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    // Security: how many wrong 6-digit codes one registration may submit
    // before verification is locked, and how long that lock lasts (15 min).
    // Without this, all one million possible codes could be tried.
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 900;

    /**
     * Display the OTP entry form for whichever registration this browser
     * session is mid-verifying (see the "pending_registration_id" flash set
     * by RegisteredUserController and AuthenticatedSessionController).
     */
    public function show(Request $request): View|RedirectResponse
    {
        $registration = $this->pendingRegistration($request);

        if (! $registration) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp', ['email' => $registration->email]);
    }

    public function store(Request $request): RedirectResponse
    {
        $registration = $this->pendingRegistration($request);
        abort_unless($registration, 419);

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        // Keyed to the registration rather than the IP, and deliberately not
        // reset by "Resend", so a 6-digit code can't be brute-forced by
        // rotating IPs or requesting fresh codes.
        $attemptsKey = 'otp-verify:'.$registration->id;

        if (RateLimiter::tooManyAttempts($attemptsKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'code' => __('Too many incorrect codes. Try again in :minutes minute(s).', [
                    'minutes' => ceil(RateLimiter::availableIn($attemptsKey) / 60),
                ]),
            ]);
        }

        if (! $registration->verifyOtp($request->string('code'))) {
            RateLimiter::hit($attemptsKey, self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'code' => __('That code is invalid or has expired. Request a new one below.'),
            ]);
        }

        RateLimiter::clear($attemptsKey);
        $registration->markEmailVerified();
        $request->session()->forget('pending_registration_id');

        $this->notifySuperAdmins($registration);

        return redirect()->route('registration.pending');
    }

    public function resend(Request $request): RedirectResponse
    {
        $registration = $this->pendingRegistration($request);
        abort_unless($registration, 419);

        $emailSent = $registration->sendOtpEmail();

        return back()->with('status', $emailSent
            ? __('A new code has been sent to :email.', ['email' => $registration->email])
            : __("We still couldn't send the email — please try again shortly, or contact your Super Admin if this continues."));
    }

    private function pendingRegistration(Request $request): ?PendingRegistration
    {
        $id = $request->session()->get('pending_registration_id');

        if (! $id) {
            return null;
        }

        return PendingRegistration::whereNull('email_verified_at')->find($id);
    }

    /**
     * Lets every Super Admin know a freshly-verified registration is waiting
     * in their approval queue (see UserManagementController::index).
     */
    private function notifySuperAdmins(PendingRegistration $registration): void
    {
        $superAdminEmails = User::where('role', User::ROLE_SUPER_ADMIN)->pluck('email');

        if ($superAdminEmails->isEmpty()) {
            return;
        }

        try {
            Mail::to($superAdminEmails)->send(new AccountPendingApproval($registration));
        } catch (\Throwable $e) {
            Log::error('Failed to send pending-approval notice: '.$e->getMessage());
        }
    }
}
