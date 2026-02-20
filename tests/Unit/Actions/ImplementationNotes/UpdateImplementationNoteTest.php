<?php

declare(strict_types=1);

use App\Actions\ImplementationNotes\UpdateImplementationNote;
use App\Models\ImplementationNote;
use App\Models\Project;

test('it updates an implementation note', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Task', 'description' => 'D']);
    $note = $task->implementationNotes()->create(['title' => 'Old', 'content' => 'Old content']);

    $result = (new UpdateImplementationNote)($note, ['title' => 'New', 'content' => 'New content']);

    expect($result)
        ->toBeInstanceOf(ImplementationNote::class)
        ->title->toBe('New')
        ->content->toBe('New content');
});

test('it preserves unchanged fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Task', 'description' => 'D']);
    $note = $task->implementationNotes()->create(['title' => 'Keep', 'content' => 'Original']);

    (new UpdateImplementationNote)($note, ['content' => 'Updated']);

    $note->refresh();

    expect($note)
        ->title->toBe('Keep')
        ->content->toBe('Updated');
});
