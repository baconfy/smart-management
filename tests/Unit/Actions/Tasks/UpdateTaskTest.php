<?php

declare(strict_types=1);

use App\Actions\Tasks\UpdateTask;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;

test('it updates a task', function (): void {
    $project = Project::create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Old', 'description' => 'Old desc']);

    $result = (new UpdateTask)($task, ['title' => 'New', 'status' => 'in_progress']);

    expect($result)
        ->toBeInstanceOf(Task::class)
        ->title->toBe('New')
        ->status->toBe(TaskStatus::InProgress);
});

test('it preserves unchanged fields', function (): void {
    $project = Project::create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Keep', 'description' => 'Keep too', 'priority' => 'high']);

    (new UpdateTask)($task, ['description' => 'Updated desc']);

    $task->refresh();

    expect($task)
        ->title->toBe('Keep')
        ->description->toBe('Updated desc')
        ->priority->toBe(TaskPriority::High);
});
