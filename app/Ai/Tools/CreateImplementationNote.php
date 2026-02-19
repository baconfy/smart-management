<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class CreateImplementationNote implements Tool
{
    /**
     * Constructor method for the class.
     *
     * @param  Project  $project  The project instance to be used within the class.
     */
    public function __construct(private Project $project) {}

    /**
     * Provides a description of the method's purpose.
     *
     * @return Stringable|string Returns a description string that explains the purpose of creating an implementation note for a task.
     */
    public function description(): Stringable|string
    {
        return 'Create an implementation note for a task. Use this to document technical details, code patterns, or implementation decisions related to a specific task.';
    }

    /**
     * Handles the incoming request to create an implementation note for a task.
     *
     * @param  Request  $request  The incoming HTTP request containing task and note details.
     * @return Stringable|string A message indicating success or failure, or a stringable result.
     */
    public function handle(Request $request): Stringable|string
    {
        $task = $this->project->tasks()->find($request['task_id']);

        if (! $task) {
            return 'Task not found in this project.';
        }

        $codeSnippets = $this->parseCodeSnippets(($request['code_snippets'] ?? null) ?: null);

        $note = $task->implementationNotes()->create([
            'title' => $request['title'],
            'content' => $request['content'],
            'code_snippets' => $codeSnippets,
        ]);

        return "Implementation note created: \"{$note->title}\" (ID: {$note->id})";
    }

    /**
     * Safely parse and validate code snippets from AI input.
     *
     * @return array<int, array{language: string, code: string}>|null
     */
    private function parseCodeSnippets(?string $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        return array_values(array_filter(
            array_map(function ($item) {
                if (! is_array($item) || ! isset($item['language'], $item['code'])) {
                    return null;
                }

                return [
                    'language' => (string) $item['language'],
                    'code' => (string) $item['code'],
                ];
            }, $decoded),
        ));
    }

    /**
     * Defines the schema for the implementation note.
     *
     * @param  JsonSchema  $schema  The JSON schema instance used to define the structure.
     * @return array The schema definition for the implementation note.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('The task ID this note belongs to.')->required(),
            'title' => $schema->string()->description('Short title for the note.')->required(),
            'content' => $schema->string()->description('Detailed content of the implementation note.')->required(),
            'code_snippets' => $schema->string()->description('Optional JSON array of code snippets. Format: [{"language":"php","code":"echo 1;"},{"language":"sql","code":"SELECT 1"}]'),
        ];
    }
}
