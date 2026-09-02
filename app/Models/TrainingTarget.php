<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['training_title', 'category', 'region', 'target'])]
class TrainingTarget extends Model
{
    protected function casts(): array
    {
        return [
            'target' => 'integer',
        ];
    }
}
