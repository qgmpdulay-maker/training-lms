<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

#[Fillable([
    'training_slug', 'training_title', 'category', 'requesting_agency', 'contact_person',
    'contact_number', 'contact_email', 'number_of_participants', 'preferred_date',
    'venue', 'purpose', 'tna_completed', 'tna_file_path', 'logistics_acknowledged',
    'signature_name', 'signed_letter_path', 'lgu', 'region', 'certificate_code', 'certificate_remarks',
    'status', 'agency_type', 'latitude', 'longitude', 'teams_organized',
    'graduates_male', 'graduates_female',
    'graduates_age_18_30', 'graduates_age_31_45', 'graduates_age_46_59', 'graduates_age_60_up',
])]
class TrainingRequest extends Model
{
    const STATUS_SUBMITTED = 'submitted';

    const STATUS_UNDER_REVIEW = 'under_review';

    const STATUS_APPROVED = 'approved';

    const STATUS_DECLINED = 'declined';

    const STATUS_COMPLETED = 'completed';

    const CERTIFICATE_REMARKS_COMPLETION = 'completion';

    const CERTIFICATE_REMARKS_PARTICIPATION = 'participation';

    const CATEGORY_APB = 'apb';

    const CATEGORY_TA = 'ta';

    const AGENCY_TYPE_LGU = 'lgu';

    const AGENCY_TYPE_NGA = 'nga';

    /**
     * Fallback map marker location (geographic centre of the Philippines)
     * for a completed training with no region and no encoded coordinates.
     */
    const DEFAULT_MAP_COORDINATES = [12.8797, 121.7740];

    public static array $statusLabels = [
        self::STATUS_SUBMITTED => 'Received',
        self::STATUS_UNDER_REVIEW => 'Being Reviewed',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_DECLINED => 'Not Approved',
        self::STATUS_COMPLETED => 'Completed',
    ];

    public static array $certificateRemarksLabels = [
        self::CERTIFICATE_REMARKS_COMPLETION => 'Completion',
        self::CERTIFICATE_REMARKS_PARTICIPATION => 'Participation',
    ];

    public static array $categoryLabels = [
        self::CATEGORY_APB => 'APB',
        self::CATEGORY_TA => 'Technical Assistance',
    ];

    public static array $agencyTypeLabels = [
        self::AGENCY_TYPE_LGU => 'LGU',
        self::AGENCY_TYPE_NGA => 'NGA',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'tna_completed' => 'boolean',
            'logistics_acknowledged' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'teams_organized' => 'integer',
            'graduates_male' => 'integer',
            'graduates_female' => 'integer',
            'graduates_age_18_30' => 'integer',
            'graduates_age_31_45' => 'integer',
            'graduates_age_46_59' => 'integer',
            'graduates_age_60_up' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The participants selected for this request when it was filed (see
     * Admin\TrainingRequestController::store). Bulk requests can carry many;
     * older, pre-bulk records have none, so callers needing "who attended"
     * should use effectiveParticipants() instead of this directly.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function trainingEvaluation(): HasOne
    {
        return $this->hasOne(TrainingEvaluation::class);
    }

    /**
     * Per-participant feedback on this training, one row per participant who
     * has submitted theirs. Distinct from trainingEvaluation(), which is the
     * admin's single aggregate record for the whole class.
     */
    public function participantEvaluations(): HasMany
    {
        return $this->hasMany(ParticipantEvaluation::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class)->withTimestamps();
    }

    /**
     * Every request that actually involves the given user — either they
     * submitted it themselves, or an admin selected them as a participant
     * in a bulk request.
     */
    public function scopeInvolvingUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('participants', fn ($q2) => $q2->where('users.id', $user->id));
        });
    }

    public function statusLabel(): string
    {
        return self::$statusLabels[$this->status] ?? ucfirst($this->status);
    }

    public function categoryLabel(): ?string
    {
        return self::$categoryLabels[$this->category] ?? null;
    }

    public function getGraduatesAttribute(): int
    {
        return $this->graduates_male + $this->graduates_female;
    }

    public function getNonCompletersAttribute(): int
    {
        return max($this->number_of_participants - $this->graduates, 0);
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function getMapCoordinatesAttribute(): array
    {
        if ($this->latitude !== null && $this->longitude !== null) {
            return [$this->latitude, $this->longitude];
        }

        return config("regions.geo.{$this->region}", self::DEFAULT_MAP_COORDINATES);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * @param  array{regions?: array, category?: string, agency_type?: string, from?: string, until?: string}|null  $filters
     */
    public function scopeFilteredBy($query, ?array $filters)
    {
        $filters ??= [];

        return $query
            ->when(! empty($filters['regions']), fn ($q) => $q->whereIn('region', $filters['regions']))
            ->when(! empty($filters['category']), fn ($q) => $q->where('category', $filters['category']))
            ->when(! empty($filters['agency_type']), fn ($q) => $q->where('agency_type', $filters['agency_type']))
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('preferred_date', '>=', $filters['from']))
            ->when(! empty($filters['until']), fn ($q) => $q->whereDate('preferred_date', '<=', $filters['until']));
    }

    /**
     * Who this request actually covers: the selected participants if any were
     * attached, otherwise the submitter — covers both the bulk admin-submitted
     * flow and older records from before participant selection existed.
     */
    public function effectiveParticipants(): Collection
    {
        if ($this->participants->isNotEmpty()) {
            return $this->participants;
        }

        return $this->user ? collect([$this->user]) : collect();
    }

    /**
     * Recompute the sex/age graduate breakdown straight from the participant
     * roster, so Regional Monitoring and the Graduates Map always match who
     * was actually on the training — no separate hand-typed numbers to keep
     * in sync. Called whenever a request is saved as Completed.
     */
    public function syncGraduateCountsFromParticipants(): void
    {
        $participants = $this->effectiveParticipants();

        $counts = [
            'graduates_male' => $participants->where('sex', 'Male')->count(),
            'graduates_female' => $participants->where('sex', 'Female')->count(),
            'graduates_age_18_30' => 0,
            'graduates_age_31_45' => 0,
            'graduates_age_46_59' => 0,
            'graduates_age_60_up' => 0,
        ];

        foreach ($participants as $participant) {
            $bracket = match (true) {
                $participant->age === null => null,
                $participant->age <= 30 => 'graduates_age_18_30',
                $participant->age <= 45 => 'graduates_age_31_45',
                $participant->age <= 59 => 'graduates_age_46_59',
                default => 'graduates_age_60_up',
            };

            if ($bracket) {
                $counts[$bracket]++;
            }
        }

        $this->update($counts);
    }
}
