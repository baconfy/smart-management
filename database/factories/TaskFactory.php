<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
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
            'title' => fake()->sentence(5),
            'description' => fake()->paragraphs(2, true),
            'phase' => fake()->optional()->randomElement(['planning', 'development', 'testing', 'deployment']),
            'milestone' => fake()->optional()->words(3, true),
            'project_status_id' => null,
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'estimate' => fake()->optional()->randomElement(['1h', '2h', '4h', '1d', '2d', '1w']),
            'sort_order' => fake()->numberBetween(0, 100),
            'parent_task_id' => null,
        ];
    }

    /**
     * Indicate that the task has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => ['priority' => TaskPriority::High]);
    }

    /**
     * Indicate that the task is a subtask of another task.
     */
    public function subtaskOf(Task $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_task_id' => $parent->id,
            'project_id' => $parent->project_id,
        ]);
    }
}
