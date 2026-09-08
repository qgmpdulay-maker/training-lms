<?php

namespace App\Models;

use App\Mail\OtpCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

#[Fillable(['name', 'age', 'sex', 'participant_type', 'agency', 'city', 'region', 'email', 'password'])]
#[Hidden(['password', 'otp_code'])]
class PendingRegistration extends Model
{
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
        ];
    }

    /**
     * The real account this registration turned into, once a Super Admin
     * approved it (see approve() below). Null until then.
     */
    public function approvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_user_id');
    }

    /**
     * Generates a fresh 6-digit OTP, stores it hashed with a 10-minute
     * expiry, and emails it. Used both right after registration and
     * whenever an unverified applicant tries to log in again.
     */
    public function sendOtpEmail(): void
    {
        $code = (string) random_int(100000, 999999);

        $this->otp_code = Hash::make($code);
        $this->otp_expires_at = now()->addMinutes(10);
        $this->save();

        try {
            Mail::to($this->email)->send(new OtpCode($this, $code));
        } catch (\Throwable $e) {
            Log::error('Failed to send OTP email: '.$e->getMessage());
        }
    }

    public function verifyOtp(string $code): bool
    {
        if (! $this->otp_code || ! $this->otp_expires_at || $this->otp_expires_at->isPast()) {
            return false;
        }

        return Hash::check($code, $this->otp_code);
    }

    public function markEmailVerified(): void
    {
        $this->email_verified_at = now();
        $this->otp_code = null;
        $this->otp_expires_at = null;
        $this->save();
    }

    /**
     * The OCD region this registration will be scoped to once approved.
     * Everyone except OCD Personnel picks this directly at registration
     * (`region`); OCD Personnel instead pick a specific OCD Regional Office
     * as their "agency", which maps to a region via config/regions.php's
     * agency_map. Null only for registrations that predate the `region`
     * field — see UserManagementController's "Unspecified Region" bucket.
     */
    public function regionLabel(): ?string
    {
        return $this->region ?: ($this->agency ? config('regions.agency_map')[$this->agency] ?? null : null);
    }

    /**
     * Creates the real account from this registration's stored details and
     * links the two records together for an audit trail. Nothing lives in
     * `users` until this runs — see UserManagementController::approve().
     */
    public function approve(): User
    {
        $user = User::create([
            'name' => $this->name,
            'age' => $this->age,
            'sex' => $this->sex,
            'participant_type' => $this->participant_type,
            'agency' => $this->agency,
            'city' => $this->city,
            // Derived the same way RegisteredUserController does it, so OCD
            // Personnel end up scoped to a region the same way admins are.
            'region' => $this->regionLabel(),
            'email' => $this->email,
            'password' => $this->password,
        ]);

        $user->email_verified_at = $this->email_verified_at;
        $user->save();

        $this->status = self::STATUS_APPROVED;
        $this->approved_user_id = $user->id;
        $this->save();

        return $user;
    }

    public function reject(): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->save();
    }
}
