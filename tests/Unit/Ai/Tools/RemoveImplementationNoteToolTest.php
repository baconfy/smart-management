<?php

declare(strict_types=1);

use App\Ai\Tools\RemoveImplementationNote;
use App\Models\ImplementationNote;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

test('remove implementation note tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = app()->make(RemoveImplementationNote::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('remove implementation note tool removes a record', function (): void {
    $project = Project::create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Task', 'description' => 'D']);

    $note = $task->implementationNotes()->create([
        'title' => 'Migration strategy',
        'content' => 'Use incremental migrations.',
    ]);

    $tool = app()->make(RemoveImplementationNote::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'implementation_note_id' => $note->id,
    ]));

    expect(ImplementationNote::count())->toBe(0);
    expect($result)->toContain('Migration strategy');
    expect($result)->toContain('removed');
});

test('remove implementation note tool returns not found for missing note', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = app()->make(RemoveImplementationNote::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'implementation_note_id' => 999,
    ]));

    expect($result)->toContain('not found');
});

test('remove implementation note tool scopes to the given project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);
    $task = $projectB->tasks()->create(['title' => 'T', 'description' => 'D']);

    $note = $task->implementationNotes()->create([
        'title' => 'Other Project Note',
        'content' => 'C',
    ]);

    $tool = app()->make(RemoveImplementationNote::class, ['project' => $projectA]);

    $result = (string) $tool->handle(new Request([
        'implementation_note_id' => $note->id,
    ]));

    expect($result)->toContain('not found');
    expect(ImplementationNote::count())->toBe(1);
});
