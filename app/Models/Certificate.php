<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['training_request_id', 'user_id', 'code', 'type', 'file_path', 'issued_on'])]
class Certificate extends Model
{
    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
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

    public function typeLabel(): ?string
    {
        return TrainingRequest::$certificateRemarksLabels[$this->type] ?? ($this->type ? ucfirst($this->type) : null);
    }
}
