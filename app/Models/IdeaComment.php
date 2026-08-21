<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdeaComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'idea_id',
        'user_id',
        'parent_id',
        'content',
        'likes_count',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(IdeaComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(IdeaComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(IdeaCommentLike::class);
    }

    public function isLikedBy(?User $user): bool
    {
        if (!$user) return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }
}
