<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'type', 'date', 'end_date', 'region', 'description', 'created_by'])]
class CalendarEvent extends Model
{
    const TYPE_HOLIDAY = 'holiday';

    const TYPE_SUSPENSION = 'suspension';

    const TYPE_OTHER = 'other';

    public static array $typeLabels = [
        self::TYPE_HOLIDAY => 'Holiday',
        self::TYPE_SUSPENSION => 'Suspension',
        self::TYPE_OTHER => 'Other',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::$typeLabels[$this->type] ?? ucfirst($this->type);
    }

    public function regionLabel(): string
    {
        return $this->region ?: 'All Regions (Nationwide)';
    }

    public function spansMultipleDays(): bool
    {
        return $this->end_date !== null && ! $this->end_date->isSameDay($this->date);
    }
}
