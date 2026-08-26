<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiFeatureSetting extends Model
{
    protected $fillable = [
        'feature',
        'provider',
        'model',
        'reasoning_effort',
        'fallback_model',
        'fallback_reasoning_effort',
        'enabled',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'options' => 'array',
        ];
    }
}
