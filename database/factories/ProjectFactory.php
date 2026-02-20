<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'color' => fake()->hexColor(),
            'settings' => null,
        ];
    }

    /**
     * Indicate that the project has custom settings.
     */
    public function withSettings(array $settings = []): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => $settings ?: ['notifications' => true, 'auto_assign' => false],
        ]);
    }
}
