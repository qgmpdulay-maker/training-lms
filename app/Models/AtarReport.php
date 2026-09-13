<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A narrative After Training Activity Report — the actual multi-page
 * document (Background/Objectives/Highlights/Issues/Ways Forward, plus
 * Declaration of Graduates + L1/L2 evaluation annexes), as opposed to
 * AtarRecord, which is the flat "Training Database" tracker row imported
 * from regional CSVs. The two are unrelated tables serving different
 * purposes — don't confuse them.
 *
 * Can optionally link back to a TrainingRequest (training_request_id) when
 * it was auto-generated from one via AtarReportGenerator; when written from
 * scratch, training_request_id is null and every field is hand-typed.
 */
#[Fillable([
    'training_request_id', 'created_by', 'title', 'venue', 'date_range', 'funding_source',
    'background', 'objectives', 'attendees_narrative', 'highlights', 'issues_and_concerns',
    'ways_forward', 'graduates_summary', 'photos', 'graduates_list', 'dropouts_list',
    'lecturers_list', 'signatories', 'l1_modules', 'l1_analysis', 'l2_stats', 'l2_analysis',
    'status',
])]
class AtarReport extends Model
{
    const STATUS_DRAFT = 'draft';

    const STATUS_FINAL = 'final';

    public static array $statusLabels = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_FINAL => 'Finalized',
    ];

    protected function casts(): array
    {
        return [
            // Repeatable-row sections edited on the form (see edit.blade.php's
            // Alpine component) and rendered as tables in the PDF annexes.
            'photos' => 'array',
            'graduates_list' => 'array',
            'dropouts_list' => 'array',
            'lecturers_list' => 'array',
            'signatories' => 'array',
            'l1_modules' => 'array',
            'l2_stats' => 'array',
        ];
    }

    /**
     * The training this report was generated from, if any. Null for a
     * report written from scratch.
     */
    public function trainingRequest(): BelongsTo
    {
        return $this->belongsTo(TrainingRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return self::$statusLabels[$this->status] ?? ucfirst($this->status);
    }
}
