<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConversationMessage>
 */
class ConversationMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'project_agent_id' => null,
            'agent' => null,
            'role' => fake()->randomElement(['user', 'assistant']),
            'content' => fake()->paragraphs(2, true),
            'attachments' => null,
            'tool_calls' => null,
            'tool_results' => null,
            'usage' => null,
            'meta' => null,
        ];
    }

    /**
     * Indicate that the message is from a user.
     */
    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'user']);
    }

    /**
     * Indicate that the message is from an assistant.
     */
    public function fromAssistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
            'agent' => fake()->randomElement(['architect', 'analyst', 'pm']),
            'usage' => ['prompt_tokens' => fake()->numberBetween(100, 1000), 'completion_tokens' => fake()->numberBetween(50, 500)],
        ]);
    }
}
