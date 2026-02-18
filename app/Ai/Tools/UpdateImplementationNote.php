<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\ImplementationNote;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class UpdateImplementationNote implements Tool
{
    public function __construct(private Project $project) {}

    public function description(): Stringable|string
    {
        return 'Update an existing implementation note. Use this to change the title, content, or code snippets.';
    }

    /**
     * Handles the update of an implementation note associated with the project.
     *
     * @param  Request  $request  The HTTP request containing input data for the update operation.
     * @return Stringable|string Returns a string or a Stringable object indicating the result of the operation.
     */
    public function handle(Request $request): Stringable|string
    {
        $taskIds = $this->project->tasks()->pluck('id');
        $note = ImplementationNote::whereIn('task_id', $taskIds)->find($request['implementation_note_id']);

        if (! $note) {
            return 'Implementation note not found in this project.';
        }

        $fields = array_filter([
            'title' => $request['title'] ?? null,
            'content' => $request['content'] ?? null,
            'code_snippets' => $request['code_snippets'] ?? null,
        ], fn ($value) => $value !== null);

        $note->update($fields);

        return "Implementation note updated: \"{$note->title}\" (ID: {$note->id})";
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
            'code_snippets' => $schema->array()->items(
                $schema->object()->properties([
                    'language' => $schema->string()->description('Programming language.'),
                    'code' => $schema->string()->description('The code snippet.'),
                ])
            )->description('Updated code snippets.'),
        ];
    }
}
