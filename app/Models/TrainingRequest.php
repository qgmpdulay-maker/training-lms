<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'training_slug', 'training_title', 'requesting_agency', 'contact_person',
    'contact_number', 'contact_email', 'number_of_participants', 'preferred_date',
    'venue', 'purpose', 'tna_completed', 'tna_file_path', 'logistics_acknowledged',
    'signature_name', 'signed_letter_path', 'lgu', 'certificate_code', 'certificate_remarks',
])]
class TrainingRequest extends Model
{
    const STATUS_SUBMITTED = 'submitted';

    const STATUS_UNDER_REVIEW = 'under_review';

    const STATUS_APPROVED = 'approved';

    const STATUS_DECLINED = 'declined';

    const STATUS_COMPLETED = 'completed';

    const CERTIFICATE_REMARKS_COMPLETION = 'completion';

    const CERTIFICATE_REMARKS_PARTICIPATION = 'participation';

    public static array $statusLabels = [
        self::STATUS_SUBMITTED => 'Received',
        self::STATUS_UNDER_REVIEW => 'Being Reviewed',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_DECLINED => 'Not Approved',
        self::STATUS_COMPLETED => 'Completed',
    ];

    public static array $certificateRemarksLabels = [
        self::CERTIFICATE_REMARKS_COMPLETION => 'Completion',
        self::CERTIFICATE_REMARKS_PARTICIPATION => 'Participation',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'tna_completed' => 'boolean',
            'logistics_acknowledged' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return self::$statusLabels[$this->status] ?? ucfirst($this->status);
    }
}
