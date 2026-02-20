<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\Tasks\UpdateTask as UpdateTaskAction;
use App\Enums\TaskPriority;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class UpdateTask implements Tool
{
    /**
     * Initialize the class with a Project instance.
     */
    public function __construct(private Project $project, private UpdateTaskAction $updateTask) {}

    /**
     * Provides a description of the functionality for updating an existing task.
     */
    public function description(): Stringable|string
    {
        return 'Update an existing task. Use this to change title, description, status, priority, or other fields.';
    }

    /**
     * Handles the request to update a task within the project.
     */
    public function handle(Request $request): Stringable|string
    {
        $task = $this->project->tasks()->find($request['task_id']);

        if (! $task) {
            return 'Task not found in this project.';
        }

        if (isset($data['priority']) && ! TaskPriority::tryFrom($data['priority'])) {
            unset($data['priority']);
        }

        $data = array_filter([
            'title' => $request['title'] ?? null,
            'description' => $request['description'] ?? null,
            'phase' => $request['phase'] ?? null,
            'milestone' => $request['milestone'] ?? null,
            'priority' => $request['priority'] ?? null,
            'estimate' => $request['estimate'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($statusSlug = $request['status'] ?? null) {
            $status = $this->project->statuses()->where('slug', $statusSlug)->first();

            if ($status) {
                $data['task_status_id'] = $status->id;
            }
        }

        ($this->updateTask)($task, $data);

        return "Task updated: \"{$task->title}\" (ID: {$task->id})";
    }

    /**
     * Defines the schema for updating a task.
     */
    public function schema(JsonSchema $schema): array
    {
        $slugs = $this->project->statuses()->ordered()->pluck('slug')->implode(', ');

        return [
            'task_id' => $schema->integer()->description('The ID of the task to update.')->required(),
            'title' => $schema->string()->description('New title.'),
            'description' => $schema->string()->description('New description.'),
            'phase' => $schema->string()->description('New phase.'),
            'milestone' => $schema->string()->description('New milestone.'),
            'status' => $schema->string()->description("New status slug. Available: {$slugs}."),
            'priority' => $schema->string()->description('New priority: high, medium, or low.'),
            'estimate' => $schema->string()->description('New estimate.'),
        ];
    }
}
