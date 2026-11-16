<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property int|null $topic_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $content
 * @property string|null $cover_image_path
 * @property ArticleStatus $status
 * @property ArticleVisibility $visibility
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Team $team
 * @property-read User $user
 * @property-read User $author
 * @property-read Topic|null $topic
 */
#[Fillable([
    'team_id',
    'user_id',
    'topic_id',
    'title',
    'slug',
    'excerpt',
    'content',
    'cover_image_path',
    'status',
    'visibility',
    'published_at',
])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Article $article): void {
            if (empty($article->slug)) {
                $baseSlug = Str::slug($article->title);
                $uniqueSlug = $baseSlug;
                $counter = 1;

                while (static::withTrashed()->where('slug', $uniqueSlug)->exists()) {
                    $uniqueSlug = "{$baseSlug}-{$counter}";
                    $counter++;
                }

                $article->slug = $uniqueSlug;
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
            'status' => ArticleStatus::class,
            'visibility' => ArticleVisibility::class,
            'published_at' => 'datetime',
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
     * Get the team this article belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who authored this article.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the topic this article belongs to.
     *
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the full URL to the article cover image stored in S3/disk.
     */
    public function coverImageUrl(): ?string
    {
        if (! $this->cover_image_path) {
            return null;
        }

        if (config('filesystems.disks.s3.url')) {
            return Storage::disk(config('filesystems.default', 's3'))->url($this->cover_image_path);
        }

        return route('media.show', ['path' => $this->cover_image_path]);
    }

    /**
     * Determine if the article is currently published and visible.
     */
    public function isPublished(): bool
    {
        return $this->status === ArticleStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * Determine if the article is visible on the public news portal.
     */
    public function isPublic(): bool
    {
        return $this->visibility === ArticleVisibility::Public && $this->isPublished();
    }

    /**
     * Scope a query to only include published articles.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include publicly visible articles.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $this->scopePublished($query)
            ->where('visibility', ArticleVisibility::Public->value);
    }

    /**
     * Scope a query to only include articles for a specific team.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForTeam(Builder $query, Team|int $team): Builder
    {
        $teamId = $team instanceof Team ? $team->id : $team;

        return $query->where('team_id', $teamId);
    }
}
