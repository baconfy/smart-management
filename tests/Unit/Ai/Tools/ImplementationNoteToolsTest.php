<?php

declare(strict_types=1);

use App\Ai\Tools\CreateImplementationNote;
use App\Ai\Tools\ListImplementationNotes;
use App\Ai\Tools\UpdateImplementationNote;
use App\Models\ImplementationNote;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

// ============================================================================
// CreateImplementationNote
// ============================================================================

test('create implementation note tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    expect((string) (app()->make(CreateImplementationNote::class, ['project' => $project]))->description())->not->toBeEmpty();
});

test('create implementation note tool creates a record', function (): void {
    $project = Project::create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Setup DB', 'description' => 'D']);

    $tool = app()->make(CreateImplementationNote::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'task_id' => $task->id,
        'title' => 'Migration strategy',
        'content' => 'Use incremental migrations with rollback support.',
    ]));

    expect(ImplementationNote::count())->toBe(1);

    $note = ImplementationNote::first();

    expect($note)
        ->task_id->toBe($task->id)
        ->title->toBe('Migration strategy')
        ->content->toBe('Use incremental migrations with rollback support.');

    expect($result)->toContain('Migration strategy');
});

test('create implementation note tool accepts code snippets', function (): void {
    $project = Project::create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Setup DB', 'description' => 'D']);

    $tool = app()->make(CreateImplementationNote::class, ['project' => $project]);

    $tool->handle(new Request([
        'task_id' => $task->id,
        'title' => 'Example query',
        'content' => 'Use this pattern for queries.',
        'code_snippets' => [
            ['language' => 'sql', 'code' => 'SELECT * FROM users;'],
        ],
    ]));

    $note = ImplementationNote::first();

    expect($note->code_snippets)->toHaveCount(1)
        ->and($note->code_snippets[0]['language'])->toBe('sql');
});

test('create implementation note tool validates task belongs to project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);
    $task = $projectB->tasks()->create(['title' => 'Other', 'description' => 'D']);

    $tool = app()->make(CreateImplementationNote::class, ['project' => $projectA]);

    $result = (string) $tool->handle(new Request([
        'task_id' => $task->id,
        'title' => 'Hacked',
        'content' => 'Should not work.',
    ]));

    expect($result)->toContain('not found');
    expect(ImplementationNote::count())->toBe(0);
});

// ============================================================================
// ListImplementationNotes
// ============================================================================

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

// ============================================================================
// UpdateImplementationNote
// ============================================================================

test('update implementation note tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    expect((string) (app()->make(UpdateImplementationNote::class, ['project' => $project]))->description())->not->toBeEmpty();
});

test('update implementation note tool updates a record', function (): void {
    $project = Project::create(['name' => 'Test']);
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
    $project = Project::create(['name' => 'Test']);
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
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);
    $task = $projectB->tasks()->create(['title' => 'T', 'description' => 'D']);
    $note = $task->implementationNotes()->create(['title' => 'Other', 'content' => 'C']);

    $result = (string) (app()->make(UpdateImplementationNote::class, ['project' => $projectA]))->handle(new Request([
        'implementation_note_id' => $note->id,
        'title' => 'Hacked',
    ]));

    expect($result)->toContain('not found');
    expect($note->refresh()->title)->toBe('Other');
});
