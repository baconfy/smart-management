<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class UpdateTask implements Tool
{
    /**
     * Constructor method for the Lucius application.
     *
     * @param  Project  $project  The project instance to be managed by this application.
     */
    public function __construct(private Project $project) {}

    /**
     * Provides a description of the functionality for updating an existing task.
     *
     * @return Stringable|string A description detailing the task update capabilities, such as modifying the title, description, status, priority, or other fields.
     */
    public function description(): Stringable|string
    {
        return 'Update an existing task. Use this to change title, description, status, priority, or other fields.';
    }

    /**
     * Handles the request to update a task within the project.
     *
     * @param  Request  $request  The incoming HTTP request containing task update data.
     * @return Stringable|string A response indicating the result of the operation.
     */
    public function handle(Request $request): Stringable|string
    {
        $task = $this->project->tasks()->find($request['task_id']);

        if (! $task) {
            return 'Task not found in this project.';
        }

        $task->update(array_filter([
            'title' => $request['title'] ?? null,
            'description' => $request['description'] ?? null,
            'phase' => $request['phase'] ?? null,
            'milestone' => $request['milestone'] ?? null,
            'status' => $request['status'] ?? null,
            'priority' => $request['priority'] ?? null,
            'estimate' => $request['estimate'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));

        return "Task updated: \"{$task->title}\" (ID: {$task->id})";
    }

    /**
     * Defines the schema for updating a task within the Lucius application.
     *
     * @param  JsonSchema  $schema  The JSON schema instance used to define the structure of the task data.
     * @return array The array representing the schema definition with task properties, descriptions, and constraints.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('The ID of the task to update.')->required(),
            'title' => $schema->string()->description('New title.'),
            'description' => $schema->string()->description('New description.'),
            'phase' => $schema->string()->description('New phase.'),
            'milestone' => $schema->string()->description('New milestone.'),
            'status' => $schema->string()->description('New status: backlog, in_progress, done, or blocked.'),
            'priority' => $schema->string()->description('New priority: high, medium, or low.'),
            'estimate' => $schema->string()->description('New estimate.'),
        ];
    }
}
