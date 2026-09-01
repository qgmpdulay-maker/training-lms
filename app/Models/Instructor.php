<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'email', 'phone', 'sex', 'position', 'training_type', 'specialization', 'certification', 'certificate_code', 'deployment', 'deployment_date', 'deployment_role', 'agency_organization', 'lgu', 'region', 'rating', 'complaints'])]
class Instructor extends Model
{
    protected function casts(): array
    {
        return [
            'deployment_date' => 'date',
        ];
    }

    public function trainingRequests(): BelongsToMany
    {
        return $this->belongsToMany(TrainingRequest::class)->withTimestamps();
    }

    /**
     * Best-effort: if exactly one instructor teaches this training type,
     * roll the average trainer rating — pooled from the admin's own L1
     * entries and every participant's per-module trainer rating — into
     * their profile. Called after either side saves an evaluation, so the
     * Overall Trainer's Rating shown on the Instructors tab is genuinely
     * auto-generated from all L1 data, not just whichever source saved last.
     */
    public static function reflectRatingForTraining(string $trainingTitle): void
    {
        $instructors = static::where('training_type', $trainingTitle)->get();

        if ($instructors->count() !== 1) {
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

        $instructors->first()->update(['rating' => round($ratings->avg(), 2)]);
    }
}
