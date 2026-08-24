<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaHierarchyHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'idea_id',
        'old_parent_idea_id',
        'new_parent_idea_id',
        'changed_by_user_id',
        'note',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function oldParentIdea(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'old_parent_idea_id');
    }

    public function newParentIdea(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'new_parent_idea_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
