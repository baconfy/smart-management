<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\Tasks\DeleteTask as DeleteTaskAction;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class RemoveTask implements Tool
{
    /**
     * Initialize the class with a Project instance.
     */
    public function __construct(private Project $project, private DeleteTaskAction $deleteTask) {}

    /**
     * Get the description of the task removal process.
     */
    public function description(): Stringable|string
    {
        return 'Remove a task from the project. Use this when a task is no longer needed or was created by mistake.';
    }

    /**
     * Handle the request to remove a task associated with the project.
     */
    public function handle(Request $request): Stringable|string
    {
        $task = $this->project->tasks()->find($request['task_id']);

        if (! $task) {
            return 'Task not found in this project.';
        }

        ($this->deleteTask)($task);

        return "Task removed: \"{$task->title}\" (ID: {$task->id})";
    }

    /**
     * Define and return the schema for removing a task.
     *
     * @param  JsonSchema  $schema  The JSON schema builder instance.
     * @return array The structured schema definition for the task removal request.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('The ID of the task to remove.')->required(),
        ];
    }
}
