<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'phone_number',
        'conversation_status',
        'handoff_to_human',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }
}
