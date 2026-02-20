<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\ImplementationNotes\UpdateImplementationNote as UpdateImplementationNoteAction;
use App\Models\ImplementationNote;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class UpdateImplementationNote implements Tool
{
    /**
     * Initialize the class with a Project instance.
     */
    public function __construct(private Project $project, private UpdateImplementationNoteAction $updateImplementationNote) {}

    /**
     * Provides the description for the operation of updating an implementation note.
     */
    public function description(): Stringable|string
    {
        return 'Update an existing implementation note. Use this to change the title, content, or code snippets.';
    }

    /**
     * Handles the update of an implementation note associated with the project.
     */
    public function handle(Request $request): Stringable|string
    {
        $taskIds = $this->project->tasks()->pluck('id');
        $note = ImplementationNote::whereIn('task_id', $taskIds)->find($request['implementation_note_id']);

        if (! $note) {
            return 'Implementation note not found in this project.';
        }

        $codeSnippets = $this->parseCodeSnippets(($request['code_snippets'] ?? null) ?: null);

        $fields = array_filter([
            'title' => ($request['title'] ?? null) ?: null,
            'content' => ($request['content'] ?? null) ?: null,
            'code_snippets' => $codeSnippets,
        ], fn ($value) => $value !== null);

        ($this->updateImplementationNote)($note, $fields);

        return "Implementation note updated: \"{$note->title}\" (ID: {$note->id})";
    }

    /**
     * Safely parse and validate code snippets from AI input.
     *
     * @return array<int, array{language: string, code: string}>|null
     */
    private function parseCodeSnippets(array|string|null $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

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
     * Defines the schema for updating an implementation note.
     *
     * @param  JsonSchema  $schema  The schema instance used to define the structure of the data.
     * @return array The schema describing the structure and constraints of the update request.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'implementation_note_id' => $schema->integer()->description('The ID of the note to update.')->required(),
            'title' => $schema->string()->description('New title.'),
            'content' => $schema->string()->description('New content.'),
            'code_snippets' => $schema->string()->description('Updated code snippets as JSON array. Format: [{"language":"php","code":"echo 1;"},{"language":"sql","code":"SELECT 1"}]'),
        ];
    }
}
