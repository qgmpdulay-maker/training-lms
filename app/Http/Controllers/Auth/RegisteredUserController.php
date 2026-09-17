<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * Government account: registering doesn't create a `users` row or grant
     * access on its own. It lands in `pending_registrations` until the email
     * is OTP-verified and a Super Admin approves it (see
     * PendingRegistration::approve(), AuthenticatedSessionController, and
     * UserManagementController).
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Only OCD Personnel pick their OCD Regional Office (which drives region
        // scoping, see config/regions.php); everyone else just gives their city.
        $isOcdPersonnel = $request->input('participant_type') === 'OCD Personnel';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'sex' => ['required', 'in:Male,Female,Other'],
            'participant_type' => ['required', 'string', 'max:255'],
            'agency' => [$isOcdPersonnel ? 'required' : 'nullable', 'string', 'max:255'],
            'city' => [$isOcdPersonnel ? 'nullable' : 'required', 'string', 'max:255'],
            // Everyone except OCD Personnel picks their region directly here —
            // OCD Personnel get theirs from the OCD Regional Office they pick
            // as their agency instead (see PendingRegistration::regionLabel()).
            'region' => [$isOcdPersonnel ? 'nullable' : 'required', 'string', 'in:'.implode(',', config('regions.list'))],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // A previous attempt under this email (abandoned before OTP
        // verification, or rejected and trying again) is replaced rather than
        // blocked. One that's already verified and sitting in the approval
        // queue is not — otherwise anyone who knows the address could reset
        // it and knock the real applicant out of the queue.
        $awaitingApproval = PendingRegistration::where('email', $validated['email'])
            ->where('status', PendingRegistration::STATUS_PENDING)
            ->whereNotNull('email_verified_at')
            ->exists();

        if ($awaitingApproval) {
            throw ValidationException::withMessages([
                'email' => __('This email already has a registration awaiting Super Admin approval.'),
            ]);
        }

        $registration = PendingRegistration::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'age' => $validated['age'],
                'sex' => $validated['sex'],
                'participant_type' => $validated['participant_type'],
                'agency' => $validated['agency'] ?? null,
                'city' => $validated['city'] ?? null,
                'region' => $validated['region'] ?? null,
                'password' => Hash::make($validated['password']),
            ]
        );

        // Reset in case this reuses a previous (rejected, or abandoned
        // mid-verification) attempt under the same email.
        $registration->status = PendingRegistration::STATUS_PENDING;
        $registration->email_verified_at = null;
        $registration->approved_user_id = null;
        $registration->save();

        $emailSent = $registration->sendOtpEmail();

        $request->session()->put('pending_registration_id', $registration->id);

        $redirect = redirect()->route('otp.show');

        if (! $emailSent) {
            $redirect->with('status', __("We couldn't send your verification email just now. Wait a moment and tap \"Resend it\" below, or contact your Super Admin if this keeps happening."));
        }

        return $redirect;
    }
}
