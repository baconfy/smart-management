<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\ImplementationNotes\DeleteImplementationNote as DeleteImplementationNoteAction;
use App\Models\ImplementationNote;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class RemoveImplementationNote implements Tool
{
    /**
     * Initialize the class with a Project instance.
     */
    public function __construct(private Project $project, private DeleteImplementationNoteAction $deleteImplementationNote) {}

    /**
     * Get the description of the implementation note removal process.
     */
    public function description(): Stringable|string
    {
        return 'Remove an implementation note from the project. Use this when a note is no longer relevant or was created by mistake.';
    }

    /**
     * Handle the request to remove an implementation note associated with the project.
     */
    public function handle(Request $request): Stringable|string
    {
        $taskIds = $this->project->tasks()->pluck('id');
        $note = ImplementationNote::whereIn('task_id', $taskIds)->find($request['implementation_note_id']);

        if (! $note) {
            return 'Implementation note not found in this project.';
        }

        ($this->deleteImplementationNote)($note);

        return "Implementation note removed: \"{$note->title}\" (ID: {$note->id})";
    }

    /**
     * Define and return the schema for removing an implementation note.
     *
     * @param  JsonSchema  $schema  The JSON schema builder instance.
     * @return array The structured schema definition for the implementation note removal request.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'implementation_note_id' => $schema->integer()->description('The ID of the implementation note to remove.')->required(),
        ];
    }
}
