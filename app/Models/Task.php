<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    public const STATUSES = ['pendiente', 'en_progreso', 'completada', 'cancelada'];

    public const PRIORITIES = ['baja', 'normal', 'alta'];

    public const PARTICIPATION_MODES = ['private', 'open'];

    protected $fillable = [
        'created_by_user_id',
        'assigned_to_user_id',
        'idea_id',
        'parent_task_id',
        'title',
        'description',
        'status',
        'priority',
        'participation_mode',
        'due_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id')->orderBy('due_at')->orderBy('title');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class);
    }

    public function volunteers(): HasMany
    {
        return $this->hasMany(TaskVolunteer::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class)->orderBy('created_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['completada', 'cancelada']);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'en_progreso' => 'En progreso',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada',
            default => 'Pendiente',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'alta' => 'Alta',
            'baja' => 'Baja',
            default => 'Normal',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at?->isPast() && ! in_array($this->status, ['completada', 'cancelada'], true);
    }
}
