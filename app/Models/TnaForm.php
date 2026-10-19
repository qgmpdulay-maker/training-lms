<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One Training Needs Assessment questionnaire — the questions a given
 * participant type is asked. Editable by the Super Admin (see
 * Admin\TnaFormController); what participants fill in is rendered from these
 * rows rather than from a hardcoded template.
 */
#[Fillable(['key', 'label', 'participant_type', 'description', 'is_active'])]
class TnaForm extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TnaQuestion::class)->orderBy('position');
    }

    /**
     * The form a participant of this type should fill in: their own type's, or
     * the fallback (participant_type null) when CDTI hasn't mapped the type.
     */
    public static function forParticipantType(?string $participantType): ?self
    {
        $query = self::with('questions.options')->where('is_active', true);

        return (clone $query)->where('participant_type', $participantType)->first()
            ?? $query->whereNull('participant_type')->first();
    }
}
