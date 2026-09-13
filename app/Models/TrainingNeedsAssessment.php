<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type', 'answers', 'category_scores', 'top_category', 'max_hours',
    'recommended_training_slug', 'recommended_training_title', 'recommended_training_category',
])]
class TrainingNeedsAssessment extends Model
{
    const TYPE_GENERIC = 'generic';

    const TYPE_ACADEME = 'academe';

    const TYPE_NRDRRMC = 'nrdrrmc';

    const TYPE_LGU = 'lgu';

    const TYPE_VOLUNTEER = 'volunteer';

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'category_scores' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
