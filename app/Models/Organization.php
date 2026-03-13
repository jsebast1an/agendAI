<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'wa_phone_number',
        'timezone',
    ];

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function professionals(): HasMany
    {
        return $this->hasMany(Professional::class);
    }
}
