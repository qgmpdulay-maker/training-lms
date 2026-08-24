<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'age', 'sex', 'picture', 'participant_type', 'organization', 'agency', 'region', 'mobile_number', 'landline_number', 'email', 'password', 'theme', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    const ROLE_PARTICIPANT = 'participant';

    const ROLE_ADMIN = 'admin';

    const ROLE_SUPER_ADMIN = 'super_admin';

    public function isParticipant(): bool
    {
        return $this->role === self::ROLE_PARTICIPANT;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Requests this user submitted themselves (the old self-service flow, and
     * the admin's own copy of a bulk request they filed). For "trainings this
     * participant is actually attending," use participatingTrainingRequests()
     * or the trainingRequestsInvolving() scope, which also cover bulk requests
     * an admin filed on this participant's behalf.
     *
     * @return HasMany<TrainingRequest>
     */
    public function trainingRequests(): HasMany
    {
        return $this->hasMany(TrainingRequest::class);
    }

    /**
     * Bulk requests this user was selected as a participant for.
     *
     * @return BelongsToMany<TrainingRequest>
     */
    public function participatingTrainingRequests(): BelongsToMany
    {
        return $this->belongsToMany(TrainingRequest::class)->withTimestamps();
    }

    /**
     * @return HasMany<TrainingNeedsAssessment>
     */
    public function trainingNeedsAssessments(): HasMany
    {
        return $this->hasMany(TrainingNeedsAssessment::class);
    }
}
