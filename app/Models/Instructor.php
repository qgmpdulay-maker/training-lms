<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One instructor — one person, one row.
 *
 * Until 2026-10-19 a row was really a (person × training type) pair, so
 * someone teaching three courses existed as three near-duplicate rows and
 * the profile page had to gather them back together by name. The courses
 * they teach, the certifications they hold, and the deployments they've been
 * sent on are each a child table now, because all three are many per person.
 */
#[Fillable(['name', 'email', 'phone', 'sex', 'photo_path', 'position', 'specialization', 'agency_organization', 'lgu', 'region', 'rating', 'complaints'])]
class Instructor extends Model
{
    /**
     * The courses this instructor can teach.
     */
    public function trainingTypes(): HasMany
    {
        return $this->hasMany(InstructorTrainingType::class);
    }

    /**
     * The certifications they hold, each with its own scanned file.
     */
    public function certifications(): HasMany
    {
        return $this->hasMany(InstructorCertification::class);
    }

    /**
     * Where they've been deployed, most recent first.
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(InstructorDeployment::class)->orderByDesc('deployment_date');
    }

    public function trainingRequests(): BelongsToMany
    {
        return $this->belongsToMany(TrainingRequest::class)->withTimestamps();
    }

    /**
     * Best-effort: if exactly one instructor teaches this training type, roll
     * the average trainer rating — pooled from the admin's own L1 entries and
     * every participant's per-module trainer rating — into their profile.
     * Called after either side saves an evaluation, so the Overall Trainer's
     * Rating shown on the Instructors tab is genuinely auto-generated from
     * all L1 data, not just whichever source saved last.
     *
     * The rating lands on the course row, since L1 feedback is about one
     * course; the instructor's own `rating` is then their average across
     * every course they're rated on.
     */
    public static function reflectRatingForTraining(string $trainingTitle): void
    {
        $courses = InstructorTrainingType::where('training_type', $trainingTitle)->get();

        if ($courses->count() !== 1) {
            return;
        }

        $trainingRequests = TrainingRequest::where('training_title', $trainingTitle)
            ->with(['trainingEvaluation', 'participantEvaluations'])
            ->get();

        $adminRatings = $trainingRequests->pluck('trainingEvaluation.module_ratings')->filter()->flatten(1)->pluck('trainer_rating');
        $participantRatings = $trainingRequests->pluck('participantEvaluations')->flatten(1)
            ->pluck('module_ratings')->filter()->flatten(1)->pluck('trainer_rating');

        $ratings = $adminRatings->merge($participantRatings)->filter(fn ($r) => is_numeric($r));

        if ($ratings->isEmpty()) {
            return;
        }

        $course = $courses->first();
        $course->update(['rating' => round($ratings->avg(), 2)]);

        $course->instructor?->refreshOverallRating();
    }

    /**
     * Average of this instructor's per-course ratings — what the roster and
     * profile header show. Null until at least one course has been rated.
     */
    public function refreshOverallRating(): void
    {
        $ratings = $this->trainingTypes()->whereNotNull('rating')->pluck('rating')->filter(fn ($r) => is_numeric($r));

        $this->update(['rating' => $ratings->isNotEmpty() ? round($ratings->avg(), 2) : null]);
    }

    /**
     * "ICS, CBDRRM, RDANA" — the roster shows every course on one line.
     */
    public function trainingTypeList(): string
    {
        return $this->trainingTypes->pluck('training_type')->filter()->join(', ');
    }
}
