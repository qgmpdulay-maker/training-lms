<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tna_question_id', 'position', 'label', 'category', 'points', 'hours'])]
class TnaOption extends Model
{
    public function question(): BelongsTo
    {
        return $this->belongsTo(TnaQuestion::class, 'tna_question_id');
    }
}
