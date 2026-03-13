<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolCallLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'conversation_id',
        'patient_id',
        'tool_name',
        'input',
        'result',
        'duration_ms',
        'success',
        'error_message',
    ];

    protected $casts = [
        'input' => 'array',
        'result' => 'array',
        'success' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
