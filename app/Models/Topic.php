<?php

namespace App\Models;

use Database\Factories\TopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $team_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $color
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $team
 * @property-read Collection<int, Article> $articles
 */
#[Fillable(['team_id', 'name', 'slug', 'description', 'color', 'is_active'])]
class Topic extends Model
{
    /** @use HasFactory<TopicFactory> */
    use HasFactory;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Topic $topic): void {
            if (empty($topic->slug)) {
                $topic->slug = Str::slug($topic->name);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the team this topic belongs to, if it is not global.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the articles categorized under this topic.
     *
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Scope a query to only include active topics.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include global topics.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('team_id');
    }

    /**
     * Scope a query to topics available to a given team (global + team specific).
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeAvailableFor(Builder $query, Team|int $team): Builder
    {
        $teamId = $team instanceof Team ? $team->id : $team;

        return $query->where(function (Builder $subQuery) use ($teamId): void {
            $subQuery->whereNull('team_id')
                ->orWhere('team_id', $teamId);
        });
    }
}
