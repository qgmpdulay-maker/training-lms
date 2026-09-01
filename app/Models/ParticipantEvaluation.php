<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['training_request_id', 'user_id', 'module_ratings', 'instructor_ratings', 'overall_comments'])]
class ParticipantEvaluation extends Model
{
    protected function casts(): array
    {
        return [
            'module_ratings' => 'array',
            'instructor_ratings' => 'array',
        ];
    }

    public function trainingRequest(): BelongsTo
    {
        return $this->belongsTo(TrainingRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
