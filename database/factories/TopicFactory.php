<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'team_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the topic is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the topic belongs to a specific team.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn (array $attributes): array => [
            'team_id' => $team->id,
        ]);
    }
}
