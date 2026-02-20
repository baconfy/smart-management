<?php

declare(strict_types=1);

use App\Actions\Tasks\UpdateTask;
use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\Task;

test('it updates a task', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $status = $project->statuses()->create(['name' => 'In Progress', 'slug' => 'in-progress', 'position' => 1]);
    $task = $project->tasks()->create(['title' => 'Old', 'description' => 'Old desc']);

    $result = (new UpdateTask)($task, ['title' => 'New', 'task_status_id' => $status->id]);

    expect($result)
        ->toBeInstanceOf(Task::class)
        ->title->toBe('New')
        ->task_status_id->toBe($status->id);
});

test('it preserves unchanged fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Keep', 'description' => 'Keep too', 'priority' => 'high']);

    (new UpdateTask)($task, ['description' => 'Updated desc']);

    $task->refresh();

    expect($task)
        ->title->toBe('Keep')
        ->description->toBe('Updated desc')
        ->priority->toBe(TaskPriority::High);
});
