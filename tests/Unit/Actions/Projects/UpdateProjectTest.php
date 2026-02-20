<?php

declare(strict_types=1);

use App\Actions\Projects\UpdateProject;
use App\Models\Project;

test('it updates a project', function (): void {
    $project = Project::factory()->create(['name' => 'Original']);

    $result = (new UpdateProject)($project, ['name' => 'Updated']);

    expect($result)
        ->toBeInstanceOf(Project::class)
        ->name->toBe('Updated');

    expect($project->refresh()->name)->toBe('Updated');
});

test('it partially updates a project', function (): void {
    $project = Project::factory()->create(['name' => 'Test', 'description' => 'Original description']);

    (new UpdateProject)($project, ['description' => 'New description']);

    $project->refresh();

    expect($project)
        ->name->toBe('Test')
        ->description->toBe('New description');
});
