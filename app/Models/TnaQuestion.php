<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tna_form_id', 'position', 'group_label', 'prompt', 'help_text', 'kind', 'is_required'])]
class TnaQuestion extends Model
{
    const KIND_SINGLE_CHOICE = 'single_choice';

    const KIND_MULTI_CHOICE = 'multi_choice';

    const KIND_COMPETENCY = 'competency';

    const KIND_SHORT_TEXT = 'short_text';

    const KIND_NUMBER = 'number';

    /** @var array<string, string> */
    public static array $kindLabels = [
        self::KIND_SINGLE_CHOICE => 'Pick one',
        self::KIND_MULTI_CHOICE => 'Pick any that apply',
        self::KIND_COMPETENCY => 'Rate competency and importance (1-5)',
        self::KIND_SHORT_TEXT => 'Short answer',
        self::KIND_NUMBER => 'Number',
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(TnaForm::class, 'tna_form_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(TnaOption::class)->orderBy('position');
    }

    public function expectsOptions(): bool
    {
        return in_array($this->kind, [self::KIND_SINGLE_CHOICE, self::KIND_MULTI_CHOICE], true);
    }
}
