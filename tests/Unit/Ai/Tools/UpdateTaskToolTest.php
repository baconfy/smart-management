<?php

declare(strict_types=1);

use App\Ai\Tools\UpdateTask;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

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
