<?php

declare(strict_types=1);

use App\Actions\ProjectAgents\UpdateProjectAgent;
use App\Models\Project;
use App\Models\ProjectAgent;

test('it updates a project agent', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $agent = $project->agents()->create(['type' => 'custom', 'name' => 'Old', 'instructions' => 'Old instructions']);

    $result = (new UpdateProjectAgent)($agent, ['name' => 'New', 'instructions' => 'New instructions']);

    expect($result)
        ->toBeInstanceOf(ProjectAgent::class)
        ->name->toBe('New')
        ->instructions->toBe('New instructions');
});

test('it partially updates a project agent', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $agent = $project->agents()->create(['type' => 'custom', 'name' => 'Keep', 'instructions' => 'Original']);

    (new UpdateProjectAgent)($agent, ['instructions' => 'Updated']);

    $agent->refresh();

    expect($agent)
        ->name->toBe('Keep')
        ->instructions->toBe('Updated');
});
