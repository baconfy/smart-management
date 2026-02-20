<?php

declare(strict_types=1);

use App\Ai\Tools\RemoveTask;
use App\Models\Project;
use App\Models\Task;
use Laravel\Ai\Tools\Request;

test('remove task tool has a description', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(RemoveTask::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('remove task tool removes a task', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $task = $project->tasks()->create([
        'title' => 'Setup database',
        'description' => 'Create schema.',
    ]);

    $tool = app()->make(RemoveTask::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'task_id' => $task->id,
    ]));

    expect(Task::count())->toBe(0);
    expect($result)->toContain('Setup database');
    expect($result)->toContain('removed');
});

test('remove task tool returns not found for missing task', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(RemoveTask::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'task_id' => 999,
    ]));

    expect($result)->toContain('not found');
});

test('remove task tool scopes to the given project', function (): void {
    $projectA = Project::factory()->create(['name' => 'Project A']);
    $projectB = Project::factory()->create(['name' => 'Project B']);

    $task = $projectB->tasks()->create([
        'title' => 'Other Project Task',
        'description' => 'D',
    ]);

    $tool = app()->make(RemoveTask::class, ['project' => $projectA]);

    $result = (string) $tool->handle(new Request([
        'task_id' => $task->id,
    ]));

    expect($result)->toContain('not found');
    expect(Task::count())->toBe(1);
});
