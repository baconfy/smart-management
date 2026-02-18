<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class ListTasks implements Tool
{
    /**
     * Initialize the Lucius application with the given project instance.
     *
     * @param  Project  $project  The project instance to be used in the application.
     */
    public function __construct(private Project $project) {}

    /**
     * Provide a description of the functionality for listing project tasks.
     *
     * @return Stringable|string Returns a description explaining task listing with optional filters for status or priority.
     */
    public function description(): Stringable|string
    {
        return 'List tasks for the project, optionally filtered by status or priority.';
    }

    /**
     * Handle the incoming request to retrieve and format tasks for the current project.
     *
     * This method filters tasks based on optional status and priority parameters in the request,
     * then formats, and returns the tasks as a string. If no tasks match the criteria, a default
     * message is returned.
     *
     * @param  Request  $request  The HTTP request containing optional filters for tasks.
     * @return Stringable|string A formatted string of tasks, or a message if none are found.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = $this->project->tasks();

        if ($status = $request['status'] ?? null) {
            $query->where('status', TaskStatus::from($status));
        }

        if ($priority = $request['priority'] ?? null) {
            $query->where('priority', TaskPriority::from($priority));
        }

        $tasks = $query->latest()->get();

        if ($tasks->isEmpty()) {
            return 'No tasks found for this project.';
        }

        return $tasks->map(fn ($t) => "- [{$t->status->value}] [{$t->priority->value}] {$t->title} (ID: {$t->id}): {$t->description}")->implode("\n");
    }

    /**
     * Define the schema for JSON validation and filtering criteria.
     *
     * @param  JsonSchema  $schema  The JSON schema instance for defining parameters.
     * @return array The array representing the schema with descriptions for filtering.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Filter by status: backlog, in_progress, done, or blocked.'),
            'priority' => $schema->string()->description('Filter by priority: high, medium, or low.'),
        ];
    }
}
