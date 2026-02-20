<?php

declare(strict_types=1);

use App\Actions\ImplementationNotes\CreateImplementationNote;
use App\Models\ImplementationNote;
use App\Models\Project;

test('it creates an implementation note for a task', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Task', 'description' => 'D']);

    $note = (new CreateImplementationNote)($task, [
        'title' => 'Database Pattern',
        'content' => 'Use repository pattern for data access.',
    ]);

    expect($note)
        ->toBeInstanceOf(ImplementationNote::class)
        ->task_id->toBe($task->id)
        ->title->toBe('Database Pattern')
        ->content->toBe('Use repository pattern for data access.');
});

test('it accepts code snippets', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Task', 'description' => 'D']);

    $note = (new CreateImplementationNote)($task, [
        'title' => 'Example',
        'content' => 'Example note.',
        'code_snippets' => [['language' => 'php', 'code' => 'echo 1;']],
    ]);

    expect($note->code_snippets)->toBe([['language' => 'php', 'code' => 'echo 1;']]);
});

test('it scopes to the given task', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $taskA = $project->tasks()->create(['title' => 'Task A', 'description' => 'D']);
    $taskB = $project->tasks()->create(['title' => 'Task B', 'description' => 'D']);

    (new CreateImplementationNote)($taskA, ['title' => 'Note A', 'content' => 'C']);
    (new CreateImplementationNote)($taskB, ['title' => 'Note B', 'content' => 'C']);

    expect($taskA->implementationNotes)->toHaveCount(1);
    expect($taskA->implementationNotes->first()->title)->toBe('Note A');
});
