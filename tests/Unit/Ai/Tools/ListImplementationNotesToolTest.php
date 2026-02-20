<?php

declare(strict_types=1);

use App\Ai\Tools\ListImplementationNotes;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

test('list implementation notes tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    expect((string) (new ListImplementationNotes($project))->description())->not->toBeEmpty();
});

test('list implementation notes tool returns notes for a task', function (): void {
    $project = Project::create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Setup DB', 'description' => 'D']);

    $task->implementationNotes()->create(['title' => 'Note 1', 'content' => 'Content 1']);
    $task->implementationNotes()->create(['title' => 'Note 2', 'content' => 'Content 2']);

    $tool = new ListImplementationNotes($project);
    $result = (string) $tool->handle(new Request(['task_id' => $task->id]));

    expect($result)->toContain('Note 1')->toContain('Note 2');
});

test('list implementation notes tool returns all project notes when no task id', function (): void {
    $project = Project::create(['name' => 'Test']);
    $task1 = $project->tasks()->create(['title' => 'Task 1', 'description' => 'D']);
    $task2 = $project->tasks()->create(['title' => 'Task 2', 'description' => 'D']);

    $task1->implementationNotes()->create(['title' => 'Note A', 'content' => 'A']);
    $task2->implementationNotes()->create(['title' => 'Note B', 'content' => 'B']);

    $tool = new ListImplementationNotes($project);
    $result = (string) $tool->handle(new Request([]));

    expect($result)->toContain('Note A')->toContain('Note B');
});

test('list implementation notes tool returns message when empty', function (): void {
    $project = Project::create(['name' => 'Test']);

    $result = (string) (new ListImplementationNotes($project))->handle(new Request([]));

    expect($result)->toContain('No implementation notes');
});

test('list implementation notes tool only returns own project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);
    $taskA = $projectA->tasks()->create(['title' => 'T', 'description' => 'D']);
    $taskB = $projectB->tasks()->create(['title' => 'T', 'description' => 'D']);

    $taskA->implementationNotes()->create(['title' => 'Note A', 'content' => 'A']);
    $taskB->implementationNotes()->create(['title' => 'Note B', 'content' => 'B']);

    $result = (string) (new ListImplementationNotes($projectA))->handle(new Request([]));

    expect($result)->toContain('Note A')->not->toContain('Note B');
});
