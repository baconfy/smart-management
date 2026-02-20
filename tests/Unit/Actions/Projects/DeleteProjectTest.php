<?php

declare(strict_types=1);

use App\Actions\Projects\DeleteProject;
use App\Models\Project;

test('it deletes a project', function (): void {
    $project = Project::factory()->create(['name' => 'To Delete']);

    $result = (new DeleteProject)($project);

    expect($result)->toBeTrue();
    expect(Project::count())->toBe(0);
});
