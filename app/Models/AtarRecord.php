<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'region', 'atar_tracker_code', 'training_type_code', 'training_title',
    'mode_of_implementation', 'month', 'date_conducted', 'venue',
    'issues_and_concerns', 'ways_forward', 'overall_rating', 'signed',
    'dropouts', 'participation', 'graduates',
    'graduates_rdrrmc', 'graduates_lgu', 'graduates_ldrrmo', 'graduates_academe',
    'graduates_cso', 'graduates_ngo', 'graduates_volunteer', 'graduates_private_sector',
    'graduates_others', 'graduates_male', 'graduates_female', 'graduates_pwd', 'graduates_youth',
    'source_of_funds', 'budget', 'actual', 'variance',
    'l1_completed', 'l2_completed', 'date_atar_submitted', 'verified_by', 'remarks',
    'source_file_path', 'imported_by', 'training_request_id',
])]
class AtarRecord extends Model
{
    protected function casts(): array
    {
        return [
            'overall_rating' => 'decimal:2',
            'signed' => 'boolean',
            'budget' => 'decimal:2',
            'actual' => 'decimal:2',
            'variance' => 'decimal:2',
            'l1_completed' => 'boolean',
            'l2_completed' => 'boolean',
            'date_atar_submitted' => 'date',
        ];
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function trainingRequest(): BelongsTo
    {
        return $this->belongsTo(TrainingRequest::class);
    }
}
