<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Models\Team;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'topic_id' => Topic::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'cover_image_path' => null,
            'status' => ArticleStatus::Draft,
            'visibility' => ArticleVisibility::Community,
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the article is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ArticleStatus::Published,
            'published_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the article is publicly visible.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => ArticleVisibility::Public,
        ]);
    }

    /**
     * Indicate that the article is in review.
     */
    public function inReview(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ArticleStatus::InReview,
        ]);
    }

    /**
     * Indicate that the article is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ArticleStatus::Archived,
        ]);
    }

    /**
     * Set a cover image path for the article.
     */
    public function withCoverImage(string $path = 'articles/sample-cover.jpg'): static
    {
        return $this->state(fn (array $attributes): array => [
            'cover_image_path' => $path,
        ]);
    }
}
