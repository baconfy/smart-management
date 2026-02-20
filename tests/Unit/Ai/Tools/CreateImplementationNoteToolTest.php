<?php

declare(strict_types=1);

use App\Ai\Tools\CreateImplementationNote;
use App\Models\ImplementationNote;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

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
