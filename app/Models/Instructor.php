<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'email', 'phone', 'sex', 'position', 'training_type', 'specialization', 'certification', 'certificate_code', 'deployment', 'agency_organization', 'lgu', 'region', 'rating', 'complaints'])]
class Instructor extends Model
{
    public function trainingRequests(): BelongsToMany
    {
        return $this->belongsToMany(TrainingRequest::class)->withTimestamps();
    }
}
