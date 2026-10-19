<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One operational deployment of an instructor — e.g. sent to Mayon
 * Operations as Team Leader.
 *
 * A history, the same way ParticipantDeployment already was for graduates;
 * an instructor used to be limited to one current deployment held in columns
 * on their own row.
 */
#[Fillable(['instructor_id', 'deployment', 'deployment_date', 'deployment_role', 'area', 'city'])]
class InstructorDeployment extends Model
{
    protected function casts(): array
    {
        return [
            'deployment_date' => 'date',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
