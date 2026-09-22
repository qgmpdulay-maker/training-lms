<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An LGU, national government agency, academe institution, or team — the
 * bodies that request trainings from OCD.
 *
 * Membership is curated by the Super Admin only; nobody self-joins. That's
 * what makes it safe to treat a member as speaking for the body.
 */
#[Fillable(['name', 'type', 'region', 'city', 'contact_person', 'contact_email', 'contact_number', 'notes'])]
class Organization extends Model
{
    const TYPE_LGU = 'lgu';

    const TYPE_NGA = 'nga';

    const TYPE_ACADEME = 'academe';

    const TYPE_TEAM = 'team';

    /**
     * Kept distinct on purpose — these are four different kinds of body that
     * happen to share a table, not one merged concept.
     */
    public static array $typeLabels = [
        self::TYPE_LGU => 'LGU',
        self::TYPE_NGA => 'National Government Agency',
        self::TYPE_ACADEME => 'Academe',
        self::TYPE_TEAM => 'Team',
    ];

    /**
     * Short form for badges and pickers, where the full NGA label is too long.
     */
    public static array $shortTypeLabels = [
        self::TYPE_LGU => 'LGU',
        self::TYPE_NGA => 'NGA',
        self::TYPE_ACADEME => 'Academe',
        self::TYPE_TEAM => 'Team',
    ];

    /**
     * Confirmed members. Distinct from the freetext `users.organization` a
     * participant typed at signup, which is only ever a hint.
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function typeLabel(): string
    {
        return self::$typeLabels[$this->type] ?? ucfirst($this->type);
    }

    public function shortTypeLabel(): string
    {
        return self::$shortTypeLabels[$this->type] ?? strtoupper($this->type);
    }

    /**
     * Where this body sits, for display: "Fictional Town · Region III".
     */
    public function locationLabel(): string
    {
        return collect([$this->city, $this->region])->filter()->join(' · ');
    }

    public function scopeFilteredBy($query, ?string $type, ?string $region, ?string $search)
    {
        return $query
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($region, fn ($q) => $q->where('region', $region))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%");
                });
            });
    }
}
