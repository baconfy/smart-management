<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Enums\TaskPriority;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class ListTasks implements Tool
{
    /**
     * Initialize with the given project instance.
     */
    public function __construct(private Project $project) {}

    /**
     * Provide a description of the functionality for listing project tasks.
     */
    public function description(): Stringable|string
    {
        return 'List tasks for the project, optionally filtered by status or priority.';
    }

    /**
     * Handle the incoming request to retrieve and format tasks for the current project.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = $this->project->tasks()->with('status');

        if ($status = $request['status'] ?? null) {
            $query->whereHas('status', fn ($q) => $q->where('slug', $status));
        }

        if ($priority = $request['priority'] ?? null) {
            $query->where('priority', TaskPriority::from($priority));
        }

        $tasks = $query->latest()->get();

        if ($tasks->isEmpty()) {
            return 'No tasks found for this project.';
        }

        return $tasks->map(function ($t) {
            $statusName = $t->status?->name ?? 'unset';

            return "- [{$statusName}] [{$t->priority->value}] {$t->title} (ID: {$t->id}): {$t->description}";
        })->implode("\n");
    }

    /**
     * Define the schema for JSON validation and filtering criteria.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Filter by status slug (e.g. todo, in-progress, done).'),
            'priority' => $schema->string()->description('Filter by priority: high, medium, or low.'),
        ];
    }
}
