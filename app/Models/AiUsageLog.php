<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'feature',
        'provider',
        'model',
        'prompt_version',
        'request_id',
        'success',
        'escalated',
        'latency_ms',
        'input_units',
        'output_units',
        'estimated_cost_usd',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'escalated' => 'boolean',
            'estimated_cost_usd' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
