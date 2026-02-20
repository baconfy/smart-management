<?php

declare(strict_types=1);

use App\Actions\Tasks\CreateTask;
use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\Task;

test('it creates a task for a project', function (): void {
    $project = Project::create(['name' => 'Test']);
    $status = $project->statuses()->create(['name' => 'Backlog', 'slug' => 'backlog', 'position' => 0]);

    $task = (new CreateTask)($project, [
        'title' => 'Setup database',
        'description' => 'Create schema and migrations.',
        'project_status_id' => $status->id,
    ]);

    expect($task)
        ->toBeInstanceOf(Task::class)
        ->project_id->toBe($project->id)
        ->title->toBe('Setup database')
        ->description->toBe('Create schema and migrations.')
        ->project_status_id->toBe($status->id);

    $task->refresh();

    expect($task)
        ->priority->toBe(TaskPriority::Medium);
});

test('it accepts optional fields', function (): void {
    $project = Project::create(['name' => 'Test']);

    $task = (new CreateTask)($project, [
        'title' => 'Deploy API',
        'description' => 'Deploy to production.',
        'phase' => 'MVP',
        'milestone' => 'Launch',
        'priority' => 'high',
        'estimate' => '3 days',
    ]);

    expect($task)
        ->phase->toBe('MVP')
        ->milestone->toBe('Launch')
        ->priority->toBe(TaskPriority::High)
        ->estimate->toBe('3 days');
});

test('it scopes to the given project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);

    (new CreateTask)($projectA, ['title' => 'Task A', 'description' => 'D']);
    (new CreateTask)($projectB, ['title' => 'Task B', 'description' => 'D']);

    expect($projectA->tasks)->toHaveCount(1);
    expect($projectA->tasks->first()->title)->toBe('Task A');
});
