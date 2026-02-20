<?php

declare(strict_types=1);

use App\Actions\Tasks\DeleteTask;
use App\Models\Project;
use App\Models\Task;

test('it deletes a task', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $task = $project->tasks()->create(['title' => 'To Delete', 'description' => 'D']);

    $result = (new DeleteTask)($task);

    expect($result)->toBeTrue();
    expect(Task::count())->toBe(0);
});
