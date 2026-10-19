<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One certification an instructor holds, with its own scanned file.
 *
 * Replaces the single `certification` / `certificate_code` /
 * `certificate_file_path` columns on `instructors`: uploading a second
 * certificate used to overwrite the first and delete its file.
 */
#[Fillable(['instructor_id', 'title', 'code', 'file_path', 'issued_on'])]
class InstructorCertification extends Model
{
    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
