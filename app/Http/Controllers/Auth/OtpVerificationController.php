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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
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

        if (! $registration->verifyOtp($request->string('code'))) {
            throw ValidationException::withMessages([
                'code' => __('That code is invalid or has expired. Request a new one below.'),
            ]);
        }

        $registration->markEmailVerified();
        $request->session()->forget('pending_registration_id');

        $this->notifySuperAdmins($registration);

        return redirect()->route('registration.pending');
    }

    public function resend(Request $request): RedirectResponse
    {
        $registration = $this->pendingRegistration($request);
        abort_unless($registration, 419);

        $registration->sendOtpEmail();

        return back()->with('status', __('A new code has been sent to :email.', ['email' => $registration->email]));
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
