<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One course an instructor can teach. An instructor has as many of these as
 * they're qualified for — before 2026-10-19 this was a single column on
 * `instructors`, so a person teaching three courses was three instructor rows.
 *
 * `rating` is this instructor's average for this course specifically, rolled
 * up from L1 feedback by Instructor::reflectRatingForTraining().
 */
#[Fillable(['instructor_id', 'training_type', 'certificate_code', 'rating'])]
class InstructorTrainingType extends Model
{
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
