<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskStatus>
 */
class TaskStatusFactory extends Factory
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
            'project_id' => Task::factory(),
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'color' => fake()->hexColor(),
            'position' => fake()->numberBetween(0, 10),
            'is_default' => false,
            'is_closed' => false,
        ];
    }

    /**
     * Indicate that this is the default status.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => ['is_default' => true]);
    }

    /**
     * Indicate that this status represents a closed/completed state.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['is_closed' => true]);
    }
}
