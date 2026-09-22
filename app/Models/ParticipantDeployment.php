<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'deployment', 'deployment_date', 'deployment_role', 'notes', 'recorded_by'])]
class ParticipantDeployment extends Model
{
    protected function casts(): array
    {
        return [
            'deployment_date' => 'date',
        ];
    }

    /**
     * The graduate who was deployed.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who entered this record.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
