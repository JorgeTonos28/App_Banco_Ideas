<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaRelation extends Model
{
    use HasFactory;

    public const TYPES = [
        'depends_on',
        'enables',
        'complements',
        'derived_from',
        'evolves_into',
        'duplicate_of',
        'superseded_by',
        'related_to',
    ];

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'source_idea_id',
        'target_idea_id',
        'type',
        'status',
        'rationale',
        'created_by_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function sourceIdea(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'source_idea_id');
    }

    public function targetIdea(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'target_idea_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'depends_on' => 'Depende de',
            'enables' => 'Habilita',
            'complements' => 'Complementa',
            'derived_from' => 'Deriva de',
            'evolves_into' => 'Evoluciona hacia',
            'duplicate_of' => 'Posible duplicado de',
            'superseded_by' => 'Sustituida por',
            'related_to' => 'Relacionada con',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'Verificada',
            'pending' => 'Pendiente de confirmación',
            'rejected' => 'Rechazada',
            default => ucfirst($this->status),
        };
    }
}
