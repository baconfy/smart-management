<?php

declare(strict_types=1);

use App\Actions\ImplementationNotes\DeleteImplementationNote;
use App\Models\ImplementationNote;
use App\Models\Project;

test('it deletes an implementation note', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'Task', 'description' => 'D']);
    $note = $task->implementationNotes()->create(['title' => 'To Delete', 'content' => 'C']);

    $result = (new DeleteImplementationNote)($note);

    expect($result)->toBeTrue();
    expect(ImplementationNote::count())->toBe(0);
});
