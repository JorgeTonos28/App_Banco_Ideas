<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'idea_id',
        'user_id',
        'old_status',
        'new_status',
        'comment',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getOldStatusLabelAttribute(): ?string
    {
        if (!$this->old_status) return null;
        return match ($this->old_status) {
            'nueva' => 'Nueva',
            'en_revision' => 'En revisión',
            'priorizada' => 'Priorizada',
            'en_desarrollo' => 'En desarrollo',
            'implementada' => 'Implementada',
            'descartada' => 'Descartada',
            'archivada' => 'Archivada',
            default => ucfirst($this->old_status),
        };
    }

    public function getNewStatusLabelAttribute(): string
    {
        return match ($this->new_status) {
            'nueva' => 'Nueva',
            'en_revision' => 'En revisión',
            'priorizada' => 'Priorizada',
            'en_desarrollo' => 'En desarrollo',
            'implementada' => 'Implementada',
            'descartada' => 'Descartada',
            'archivada' => 'Archivada',
            default => ucfirst($this->new_status),
        };
    }
}
