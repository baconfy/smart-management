<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AgentType;
use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectAgent>
 */
class ProjectAgentFactory extends Factory
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
            'type' => fake()->randomElement(AgentType::cases()),
            'name' => fake()->words(2, true),
            'instructions' => fake()->paragraphs(2, true),
            'model' => fake()->randomElement(['gpt-4o', 'claude-sonnet-4-20250514', 'claude-opus-4-20250514']),
            'is_default' => false,
            'settings' => null,
            'tools' => null,
        ];
    }

    /**
     * Indicate that the agent is a default agent.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => ['is_default' => true]);
    }

    /**
     * Indicate that the agent has specific tools.
     */
    public function withTools(array $tools = []): static
    {
        return $this->state(fn (array $attributes) => [
            'tools' => $tools ?: ['search', 'code_analysis', 'file_reader'],
        ]);
    }
}
