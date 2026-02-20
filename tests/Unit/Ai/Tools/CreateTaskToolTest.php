<?php

declare(strict_types=1);

use App\Ai\Tools\CreateTask;
use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\Task;
use Laravel\Ai\Tools\Request;

test('create task tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    expect((string) app()->make(CreateTask::class, ['project' => $project])->description())->not->toBeEmpty();
});

test('create task tool creates a task with required fields', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = app()->make(CreateTask::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'title' => 'Setup database',
        'description' => 'Create PostgreSQL schema and migrations.',
    ]));

    expect(Task::count())->toBe(1);

    $task = Task::first();

    expect($task)
        ->project_id->toBe($project->id)
        ->title->toBe('Setup database')
        ->description->toBe('Create PostgreSQL schema and migrations.')
        ->project_status_id->toBeNull()
        ->priority->toBe(TaskPriority::Medium);

    expect($result)->toContain('Setup database');
});

test('create task tool accepts optional fields', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = app()->make(CreateTask::class, ['project' => $project]);

    $tool->handle(new Request([
        'title' => 'Deploy API',
        'description' => 'Deploy to production.',
        'phase' => 'MVP',
        'milestone' => 'Launch',
        'priority' => 'high',
        'estimate' => '3 days',
    ]));

    $task = Task::first();

    expect($task)
        ->phase->toBe('MVP')
        ->milestone->toBe('Launch')
        ->priority->toBe(TaskPriority::High)
        ->estimate->toBe('3 days');
});

test('create task tool can create subtask', function (): void {
    $project = Project::create(['name' => 'Test']);
    $parent = $project->tasks()->create(['title' => 'Parent', 'description' => 'Parent task.']);

    $tool = app()->make(CreateTask::class, ['project' => $project]);
    $tool->handle(new Request(['title' => 'Subtask', 'description' => 'Child task.', 'parent_task_id' => $parent->id]));
    $subtask = Task::where('parent_task_id', $parent->id)->first();

    expect($subtask)->not->toBeNull()->title->toBe('Subtask');
    expect($parent->subtasks)->toHaveCount(1);
});

test('create task tool scopes to project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);

    app()->make(CreateTask::class, ['project' => $projectA])->handle(new Request(['title' => 'Task A', 'description' => 'D']));
    app()->make(CreateTask::class, ['project' => $projectB])->handle(new Request(['title' => 'Task B', 'description' => 'D']));

    expect($projectA->tasks)->toHaveCount(1);
    expect($projectB->tasks)->toHaveCount(1);
});

test('create task tool handles parent_task_id as zero', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = app()->make(CreateTask::class, ['project' => $project]);

    $tool->handle(new Request(['title' => 'Task with zero parent', 'description' => 'AI sent parent_task_id as 0.', 'parent_task_id' => 0]));
    $task = Task::first();

    expect($task)
        ->parent_task_id->toBeNull()
        ->title->toBe('Task with zero parent');
});

test('create task tool handles parent_task_id as empty string', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = app()->make(CreateTask::class, ['project' => $project]);

    $tool->handle(new Request(['title' => 'Task with empty parent', 'description' => 'AI sent parent_task_id as empty string.', 'parent_task_id' => '']));
    $task = Task::first();

    expect($task)
        ->parent_task_id->toBeNull()
        ->title->toBe('Task with empty parent');
});
