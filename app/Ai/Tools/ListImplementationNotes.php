<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\ImplementationNote;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class ListImplementationNotes implements Tool
{
    /**
     * Initialize a new instance of the class with the given Project dependency.
     *
     * @param  Project  $project  The project instance to be used within the class.
     */
    public function __construct(private Project $project) {}

    /**
     * Retrieve a description of implementation notes, with optional filtering by task.
     *
     * If no task ID is provided, the method returns all notes across the project.
     *
     * @return Stringable|string A detailed description of the implementation notes.
     */
    public function description(): Stringable|string
    {
        return 'List implementation notes, optionally filtered by task. Without a task ID, returns all notes across the project.';
    }

    /**
     * Handle the incoming request to fetch implementation notes for tasks in the project.
     *
     * @param  Request  $request  The HTTP request containing optional 'task_id'.
     * @return Stringable|string A formatted response listing the implementation notes or an error message.
     *
     * - If 'task_id' is supplied, fetches implementation notes specific to the task.
     * - If 'task_id' is not supplied, fetches implementation notes for all tasks in the project.
     * - Returns a message if no task or notes are found.
     */
    public function handle(Request $request): Stringable|string
    {
        $taskId = $request['task_id'] ?? null;

        if ($taskId) {
            $task = $this->project->tasks()->find($taskId);

            if (! $task) {
                return 'Task not found in this project.';
            }

            $notes = $task->implementationNotes()->latest()->get();
        } else {
            $taskIds = $this->project->tasks()->pluck('id');
            $notes = ImplementationNote::whereIn('task_id', $taskIds)->latest()->get();
        }

        if ($notes->isEmpty()) {
            return 'No implementation notes found.';
        }

        return $notes->map(fn ($n) => "- {$n->title} (ID: {$n->id}, Task: {$n->task_id}): {$n->content}")->implode("\n");
    }

    /**
     * Define the schema for the project notes filter.
     *
     * @param  JsonSchema  $schema  The JSON schema instance for building validation rules.
     * @return array An array defining the schema structure and description.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('Filter by task ID. Omit to list all project notes.'),
        ];
    }
}
