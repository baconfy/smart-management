<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BusinessRuleStatus;
use App\Models\BusinessRule;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessRule>
 */
class BusinessRuleFactory extends Factory
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
            'description' => fake()->paragraphs(2, true),
            'category' => fake()->randomElement(['validation', 'authorization', 'business_logic', 'data_integrity']),
            'status' => BusinessRuleStatus::Active,
        ];
    }

    /**
     * Indicate that the business rule is deprecated.
     */
    public function deprecated(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BusinessRuleStatus::Deprecated]);
    }
}
