<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImplementationNote;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImplementationNote>
 */
class ImplementationNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'code_snippets' => null,
        ];
    }

    /**
     * Indicate that the note has code snippets.
     */
    public function withCodeSnippets(array $snippets = []): static
    {
        return $this->state(fn (array $attributes) => [
            'code_snippets' => $snippets ?: [
                ['language' => 'php', 'code' => '<?php echo "Hello World";'],
                ['language' => 'javascript', 'code' => 'console.log("Hello World");'],
            ],
        ]);
    }
}
