<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaudeApiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'conversation_id',
        'model',
        'input_tokens',
        'output_tokens',
        'cache_write_tokens',
        'cache_read_tokens',
        'cost_usd',
    ];

    protected $casts = [
        'cost_usd' => 'decimal:6',
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

    public function scopeForOrg(Builder $query, int $orgId): Builder
    {
        return $query->where('organization_id', $orgId);
    }
}
