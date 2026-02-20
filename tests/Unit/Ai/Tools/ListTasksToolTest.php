<?php

declare(strict_types=1);

use App\Ai\Tools\ListTasks;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

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

    $backlog = $project->statuses()->create(['name' => 'Backlog', 'slug' => 'backlog', 'position' => 0]);
    $done = $project->statuses()->create(['name' => 'Done', 'slug' => 'done', 'position' => 1]);

    $project->tasks()->create(['title' => 'Backlog Task', 'description' => 'D', 'project_status_id' => $backlog->id]);
    $project->tasks()->create(['title' => 'Done Task', 'description' => 'D', 'project_status_id' => $done->id]);

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
