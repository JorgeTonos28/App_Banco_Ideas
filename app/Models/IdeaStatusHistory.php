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
        'workflow',
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
        if (! $this->old_status) {
            return null;
        }

        return $this->statusLabel($this->old_status);
    }

    public function getNewStatusLabelAttribute(): string
    {
        return $this->statusLabel($this->new_status);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'nueva' => 'Nueva',
            'en_revision' => 'En revisión',
            'priorizada' => 'Priorizada',
            'en_desarrollo' => 'En desarrollo',
            'implementada' => 'Implementada',
            'descartada' => 'Descartada',
            'archivada' => 'Archivada',
            'capturada' => 'Capturada',
            'en_clarificacion' => 'En clarificación',
            'lista_para_actuar' => 'Lista para actuar',
            'en_ejecucion' => 'En ejecución',
            'completada' => 'Completada',
            'en_pausa' => 'En pausa',
            'not_submitted' => 'No enviada',
            'pending_review' => 'Pendiente de revisión',
            'changes_requested' => 'Cambios solicitados',
            'published' => 'Publicada',
            'rejected' => 'Rechazada',
            'unpublished' => 'Retirada de comunidad',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
