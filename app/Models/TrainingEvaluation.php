<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['module_ratings', 'pretest_score', 'posttest_score'])]
class TrainingEvaluation extends Model
{
    protected function casts(): array
    {
        return [
            'module_ratings' => 'array',
            'pretest_score' => 'integer',
            'posttest_score' => 'integer',
        ];
    }

    public function trainingRequest(): BelongsTo
    {
        return $this->belongsTo(TrainingRequest::class);
    }
}
