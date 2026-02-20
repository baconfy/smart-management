<?php

declare(strict_types=1);

use App\Actions\ProjectAgents\DeleteProjectAgent;
use App\Models\Project;
use App\Models\ProjectAgent;

test('it deletes a project agent', function (): void {
    $project = Project::create(['name' => 'Test']);
    $agent = $project->agents()->create(['type' => 'custom', 'name' => 'Agent', 'instructions' => 'Instructions']);

    $result = (new DeleteProjectAgent)($agent);

    expect($result)->toBeTrue();
    expect(ProjectAgent::count())->toBe(0);
});
