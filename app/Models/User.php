<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'job_title',
        'department',
        'regional',
        'avatar',
        'bio',
        'is_active',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    public function assignedIdeas(): HasMany
    {
        return $this->hasMany(Idea::class, 'assigned_to_user_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(IdeaRating::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IdeaComment::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(IdeaFavorite::class);
    }

    public function favoriteIdeas(): BelongsToMany
    {
        return $this->belongsToMany(Idea::class, 'idea_favorites');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            if (str_starts_with($this->avatar, 'http')) {
                return $this->avatar;
            }
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=005696&color=ffffff&bold=true';
    }

    /**
     * Compute Badges for user
     */
    public function getBadgesAttribute(): array
    {
        $badges = [];
        $ideasCount = $this->ideas()->where('visibility', 'public')->count();
        $implementedCount = $this->ideas()->where('status', 'implementada')->count();
        $hasTrending = $this->ideas()->where('innovation_score', '>=', 80)->exists();

        if ($ideasCount >= 1) {
            $badges[] = [
                'id' => 'generator',
                'name' => 'Generador de ideas',
                'icon' => 'lightbulb',
                'color' => 'bg-primary-container text-on-primary-container',
                'description' => 'Ha propuesto ideas en la plataforma.'
            ];
        }

        if ($hasTrending) {
            $badges[] = [
                'id' => 'trending',
                'name' => 'Idea en tendencia',
                'icon' => 'local_fire_department',
                'color' => 'bg-secondary-container text-on-secondary-container',
                'description' => 'Una de sus ideas alcanzó alta puntuación de innovación.'
            ];
        }

        if ($implementedCount >= 1) {
            $badges[] = [
                'id' => 'implemented',
                'name' => 'Idea implementada',
                'icon' => 'rocket_launch',
                'color' => 'bg-tertiary-container text-on-tertiary-container',
                'description' => 'Logró transformar una idea en realidad en INFOTEP.'
            ];
        }

        if ($ideasCount >= 3 || $this->ratings()->count() >= 10) {
            $badges[] = [
                'id' => 'top_innovator',
                'name' => 'Top Innovador',
                'icon' => 'emoji_events',
                'color' => 'bg-secondary-fixed text-on-secondary-fixed',
                'description' => 'Contribuyente destacado en la comunidad INNOVATEP.'
            ];
        }

        return $badges;
    }
}
