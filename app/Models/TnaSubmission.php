<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'region', 'agency_type', 'organization', 'training_topic', 'personnel_assessed',
    'date_assessed', 'submitted_by', 'status', 'results_pdf_path', 'notes',
])]
class TnaSubmission extends Model
{
    const STATUS_PENDING = 'pending';

    const STATUS_REVIEWED = 'reviewed';

    const AGENCY_TYPE_LGU = 'lgu';

    const AGENCY_TYPE_NGA = 'nga';

    public static array $statusLabels = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_REVIEWED => 'Reviewed',
    ];

    public static array $agencyTypeLabels = [
        self::AGENCY_TYPE_LGU => 'LGU',
        self::AGENCY_TYPE_NGA => 'NGA',
    ];

    protected function casts(): array
    {
        return [
            'date_assessed' => 'date',
            'personnel_assessed' => 'integer',
        ];
    }

    public function hasResultsPdf(): bool
    {
        return filled($this->results_pdf_path);
    }

    public function organizationLabel(): string
    {
        return $this->organization ?: 'Unspecified organization';
    }

    public function statusLabel(): string
    {
        return self::$statusLabels[$this->status] ?? ucfirst($this->status);
    }

    public function agencyTypeLabel(): ?string
    {
        return self::$agencyTypeLabels[$this->agency_type] ?? null;
    }

    /**
     * @param  array{regions?: array, agency_type?: string, status?: string, from?: string, until?: string}|null  $filters
     */
    public function scopeFilteredBy(Builder $query, ?array $filters): Builder
    {
        $filters ??= [];

        return $query
            ->when(! empty($filters['regions']), fn ($q) => $q->whereIn('region', $filters['regions']))
            ->when(! empty($filters['agency_type']), fn ($q) => $q->where('agency_type', $filters['agency_type']))
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('date_assessed', '>=', $filters['from']))
            ->when(! empty($filters['until']), fn ($q) => $q->whereDate('date_assessed', '<=', $filters['until']));
    }
}
