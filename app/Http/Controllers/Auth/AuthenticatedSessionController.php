<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\PendingRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Government account: someone who registered but hasn't been OTP-verified
     * and Super-Admin-approved yet has no `users` row at all — they only exist
     * in `pending_registrations` (see RegisteredUserController and
     * PendingRegistration::approve()). Their credentials are checked against
     * that table first, before ever touching the real Auth guard.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Must run before the pending-registration check below — otherwise a
        // locked-out attacker could keep guessing, since that check reveals a
        // correct password without ever reaching authenticate()'s throttle.
        $request->ensureIsNotRateLimited();

        $registration = PendingRegistration::whereIn('status', [
            PendingRegistration::STATUS_PENDING,
            PendingRegistration::STATUS_REJECTED,
        ])->where('email', $request->input('email'))->first();

        if ($registration && Hash::check((string) $request->input('password'), $registration->password)) {
            if ($registration->email_verified_at === null) {
                $emailSent = $registration->sendOtpEmail();
                $request->session()->put('pending_registration_id', $registration->id);

                $redirect = redirect()->route('otp.show');

                if (! $emailSent) {
                    $redirect->with('status', __("We couldn't send your verification email just now. Wait a moment and tap \"Resend it\" below, or contact your Super Admin if this keeps happening."));
                }

                return $redirect;
            }

            throw ValidationException::withMessages([
                'email' => $registration->status === PendingRegistration::STATUS_REJECTED
                    ? __('Your account registration was not approved. Contact your OCD Regional Office for assistance.')
                    : __("Your account is awaiting Super Admin approval. We'll email you once it's approved."),
            ]);
        }

        $request->authenticate();

        $request->session()->regenerate();

        $defaultRoute = $request->user()->isParticipant() ? 'dashboard' : 'admin.dashboard';

        return redirect()->intended(route($defaultRoute, absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
