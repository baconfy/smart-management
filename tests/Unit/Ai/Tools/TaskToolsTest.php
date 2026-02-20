<?php

declare(strict_types=1);

use App\Ai\Tools\CreateTask;
use App\Ai\Tools\ListTasks;
use App\Ai\Tools\UpdateTask;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Laravel\Ai\Tools\Request;

// ============================================================================
// CreateTask
// ============================================================================

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
        ->status->toBe(TaskStatus::Backlog)
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

// ============================================================================
// ListTasks
// ============================================================================

test('list tasks tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    expect((string) (new ListTasks($project))->description())->not->toBeEmpty();
});

test('list tasks tool returns project tasks', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->tasks()->create(['title' => 'Task 1', 'description' => 'D1']);
    $project->tasks()->create(['title' => 'Task 2', 'description' => 'D2']);

    $result = (string) (new ListTasks($project))->handle(new Request([]));

    expect($result)->toContain('Task 1')->toContain('Task 2');
});

test('list tasks tool filters by status', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->tasks()->create(['title' => 'Backlog Task', 'description' => 'D', 'status' => 'backlog']);
    $project->tasks()->create(['title' => 'Done Task', 'description' => 'D', 'status' => 'done']);

    $result = (string) (new ListTasks($project))->handle(new Request(['status' => 'backlog']));

    expect($result)->toContain('Backlog Task')->not->toContain('Done Task');
});

test('list tasks tool filters by priority', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->tasks()->create(['title' => 'High Task', 'description' => 'D', 'priority' => 'high']);
    $project->tasks()->create(['title' => 'Low Task', 'description' => 'D', 'priority' => 'low']);

    $result = (string) (new ListTasks($project))->handle(new Request(['priority' => 'high']));

    expect($result)->toContain('High Task')->not->toContain('Low Task');
});

test('list tasks tool returns message when empty', function (): void {
    $project = Project::create(['name' => 'Test']);

    $result = (string) (new ListTasks($project))->handle(new Request([]));

    expect($result)->toContain('No tasks');
});

test('list tasks tool only returns own project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);

    $projectA->tasks()->create(['title' => 'Task A', 'description' => 'D']);
    $projectB->tasks()->create(['title' => 'Task B', 'description' => 'D']);

    $result = (string) (new ListTasks($projectA))->handle(new Request([]));

    expect($result)->toContain('Task A')->not->toContain('Task B');
});

// ============================================================================
// UpdateTask
// ============================================================================

test('update task tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    expect((string) (app()->make(UpdateTask::class, ['project' => $project]))->description())->not->toBeEmpty();
});

test('update task tool updates a task', function (): void {
    $project = Project::create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Old', 'description' => 'Old desc']);

    $result = (string) (app()->make(UpdateTask::class, ['project' => $project]))->handle(new Request([
        'task_id' => $task->id,
        'title' => 'New Title',
        'status' => 'in_progress',
    ]));

    $task->refresh();

    expect($task)->title->toBe('New Title')->status->toBe(TaskStatus::InProgress);
    expect($result)->toContain('New Title');
});

test('update task tool only updates provided fields', function (): void {
    $project = Project::create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Keep', 'description' => 'Keep too', 'priority' => 'high']);

    (app()->make(UpdateTask::class, ['project' => $project]))->handle(new Request([
        'task_id' => $task->id,
        'description' => 'Updated desc',
    ]));

    $task->refresh();
    expect($task)->title->toBe('Keep')->description->toBe('Updated desc')->priority->toBe(TaskPriority::High);
});

test('update task tool scopes to project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);
    $task = $projectB->tasks()->create(['title' => 'Other', 'description' => 'D']);

    $result = (string) (app()->make(UpdateTask::class, ['project' => $projectA]))->handle(new Request([
        'task_id' => $task->id,
        'title' => 'Hacked',
    ]));

    expect($result)->toContain('not found');
    expect($task->refresh()->title)->toBe('Other');
});
