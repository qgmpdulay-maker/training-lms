<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'age', 'sex', 'picture', 'participant_type', 'organization', 'agency', 'mobile_number', 'landline_number', 'email', 'password', 'theme', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable{
    
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<\App\Models\TrainingRequest>
     */
    public function trainingRequests(): HasMany
    {
        return $this->hasMany(TrainingRequest::class);
    }
}