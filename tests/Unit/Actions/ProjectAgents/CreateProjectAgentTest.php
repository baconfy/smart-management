<?php

declare(strict_types=1);

use App\Actions\ProjectAgents\CreateProjectAgent;
use App\Models\Project;
use App\Models\ProjectAgent;

test('it creates a project agent', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $agent = (new CreateProjectAgent)($project, [
        'type' => 'custom',
        'name' => 'Test Agent',
        'instructions' => 'Do things.',
    ]);

    expect($agent)
        ->toBeInstanceOf(ProjectAgent::class)
        ->project_id->toBe($project->id)
        ->name->toBe('Test Agent')
        ->instructions->toBe('Do things.');
});

test('it scopes to the given project', function (): void {
    $projectA = Project::factory()->create(['name' => 'A']);
    $projectB = Project::factory()->create(['name' => 'B']);

    (new CreateProjectAgent)($projectA, ['type' => 'custom', 'name' => 'Agent A', 'instructions' => 'A']);
    (new CreateProjectAgent)($projectB, ['type' => 'custom', 'name' => 'Agent B', 'instructions' => 'B']);

    expect($projectA->agents)->toHaveCount(1);
    expect($projectB->agents)->toHaveCount(1);
    expect($projectA->agents->first()->name)->toBe('Agent A');
});
