<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'age', 'sex', 'picture', 'participant_type', 'organization', 'agency', 'city', 'region', 'email', 'password', 'theme', 'locale', 'position'])]
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
     * Every certificate issued to this user (one per completed training).
     * Lets pages such as Summary load all participants' certificates in a
     * single query instead of one query per participant.
     *
     * @return HasMany<Certificate>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * The LGU / NGA / academe body / team this user belongs to, as confirmed
     * by a Super Admin. Null until someone assigns them.
     *
     * Not to be confused with the freetext `organization` column, which is
     * whatever they typed about themselves at signup — a hint for the admin
     * doing the assigning, never proof of affiliation.
     */
    public function assignedOrganization(): BelongsTo
    {
        // Named `assignedOrganization`, not `organization`: the freetext
        // `organization` COLUMN already exists on this table, and an attribute
        // always shadows a same-named relation — `$user->organization` would
        // silently hand back the unverified string instead of this record.
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * Users a Super Admin hasn't placed into an organization yet.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('organization_id');
    }

    /**
     * Operational deployments this graduate has been sent on since finishing
     * a training — newest first. Recorded by Regional Admins for their own
     * region (see Admin\ParticipantDeploymentController).
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(ParticipantDeployment::class)->orderByDesc('deployment_date');
    }

    /**
     * Has this user finished at least one training? Deployments are only
     * recorded against graduates, so this gates who appears on that page.
     */
    public function isGraduate(): bool
    {
        return TrainingRequest::completed()->involvingUser($this)->exists();
    }

    /**
     * Users who have completed at least one training.
     */
    public function scopeGraduates($query)
    {
        // "Involved in a completed training" covers both paths, the same way
        // TrainingRequest::scopeInvolvingUser() does in reverse: a request the
        // user filed themselves, or one an admin selected them onto.
        return $query->where(function ($q) {
            $q->whereHas('trainingRequests', fn ($sub) => $sub->where('status', TrainingRequest::STATUS_COMPLETED))
                ->orWhereHas('participatingTrainingRequests', fn ($sub) => $sub->where('status', TrainingRequest::STATUS_COMPLETED));
        });
    }

    /**
     * @return HasMany<TrainingNeedsAssessment>
     */
    public function trainingNeedsAssessments(): HasMany
    {
        return $this->hasMany(TrainingNeedsAssessment::class);
    }
}
