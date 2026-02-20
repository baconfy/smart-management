<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DecisionStatus;
use App\Models\Decision;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Decision>
 */
class DecisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'choice' => fake()->paragraph(),
            'reasoning' => fake()->paragraphs(2, true),
            'alternatives_considered' => [
                fake()->sentence(),
                fake()->sentence(),
            ],
            'context' => fake()->paragraph(),
            'status' => DecisionStatus::Active,
        ];
    }

    /**
     * Indicate that the decision is superseded.
     */
    public function superseded(): static
    {
        return $this->state(fn (array $attributes) => ['status' => DecisionStatus::Superseded]);
    }

    /**
     * Indicate that the decision is deprecated.
     */
    public function deprecated(): static
    {
        return $this->state(fn (array $attributes) => ['status' => DecisionStatus::Deprecated]);
    }
}
