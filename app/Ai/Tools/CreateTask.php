<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\Tasks\CreateTask as CreateTaskAction;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class CreateTask implements Tool
{
    /**
     * Initialize a new instance of the class with the given Project dependency.
     */
    public function __construct(private Project $project, private CreateTaskAction $createTask) {}

    /**
     * Get the description of the task creation functionality.
     */
    public function description(): Stringable|string
    {
        return 'Create a task for the project. Use this to track work items, features, bugs, or any actionable item.';
    }

    /**
     * Handle the creation of a new task based on the given request data.
     */
    public function handle(Request $request): Stringable|string
    {
        $parentTaskId = $request['parent_task_id'] ?? null;
        $statusSlug = ($request['status'] ?? null) ?: null;

        $status = $statusSlug
            ? $this->project->statuses()->where('slug', $statusSlug)->first()
            : $this->project->statuses()->default()->first();

        $task = ($this->createTask)($this->project, array_filter([
            'title' => $request['title'] ?? null,
            'description' => $request['description'] ?? null,
            'phase' => $request['phase'] ?? null,
            'milestone' => $request['milestone'] ?? null,
            'task_status_id' => $status?->id,
            'priority' => $request['priority'] ?? null,
            'estimate' => $request['estimate'] ?? null,
            'parent_task_id' => $parentTaskId ?: null,
        ], fn ($value) => $value !== null && $value !== ''));

        $statusName = $status?->name ?? 'none';

        return "Task created: \"{$task->title}\" (ID: {$task->id}, Status: {$statusName})";
    }

    /**
     * Define the schema for a task with validation rules and descriptions.
     *
     * @param  JsonSchema  $schema  The JSON schema instance for defining task properties.
     * @return array The schema definition for a task including title, description, phase, milestone, priority, estimate, and parent task ID.
     */
    public function schema(JsonSchema $schema): array
    {
        $slugs = $this->project->statuses()->ordered()->pluck('slug')->implode(', ');

        return [
            'title' => $schema->string()->description('Short title for the task.')->required(),
            'description' => $schema->string()->description('Detailed description of what needs to be done.')->required(),
            'phase' => $schema->string()->description('Project phase (e.g. MVP, v2).'),
            'milestone' => $schema->string()->description('Milestone this task belongs to.'),
            'status' => $schema->string()->description("Task status slug. Available: {$slugs}. Defaults to the project default status."),
            'priority' => $schema->string()->description('Priority: high, medium, or low. Defaults to medium.'),
            'estimate' => $schema->string()->description('Time estimate (e.g. "3 days", "2 hours").'),
            'parent_task_id' => $schema->integer()->description('Parent task ID to create a subtask.'),
        ];
    }
}
