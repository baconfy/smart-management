<?php

declare(strict_types=1);

use App\Ai\Tools\UpdateImplementationNote;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

test('update implementation note tool has a description', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    expect((string) (app()->make(UpdateImplementationNote::class, ['project' => $project]))->description())->not->toBeEmpty();
});

test('update implementation note tool updates a record', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Task', 'description' => 'D']);
    $note = $task->implementationNotes()->create(['title' => 'Old', 'content' => 'Old content']);

    $tool = app()->make(UpdateImplementationNote::class, ['project' => $project]);
    $result = (string) $tool->handle(new Request([
        'implementation_note_id' => $note->id,
        'title' => 'New Title',
        'content' => 'Updated content.',
    ]));

    $note->refresh();

    expect($note)->title->toBe('New Title')->content->toBe('Updated content.');
    expect($result)->toContain('New Title');
});

test('update implementation note tool only updates provided fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Task', 'description' => 'D']);
    $note = $task->implementationNotes()->create(['title' => 'Keep', 'content' => 'Keep too']);

    (app()->make(UpdateImplementationNote::class, ['project' => $project]))->handle(new Request([
        'implementation_note_id' => $note->id,
        'content' => 'Updated only this.',
    ]));

    $note->refresh();
    expect($note)->title->toBe('Keep')->content->toBe('Updated only this.');
});

test('update implementation note tool scopes to project', function (): void {
    $projectA = Project::factory()->create(['name' => 'A']);
    $projectB = Project::factory()->create(['name' => 'B']);
    $task = $projectB->tasks()->create(['title' => 'T', 'description' => 'D']);
    $note = $task->implementationNotes()->create(['title' => 'Other', 'content' => 'C']);

    $result = (string) (app()->make(UpdateImplementationNote::class, ['project' => $projectA]))->handle(new Request([
        'implementation_note_id' => $note->id,
        'title' => 'Hacked',
    ]));

    expect($result)->toContain('not found');
    expect($note->refresh()->title)->toBe('Other');
});
