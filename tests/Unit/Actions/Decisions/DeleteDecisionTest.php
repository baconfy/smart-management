<?php

declare(strict_types=1);

use App\Actions\Decisions\DeleteDecision;
use App\Models\Decision;
use App\Models\Project;

test('it deletes a decision', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $decision = $project->decisions()->create([
        'title' => 'To Delete',
        'choice' => 'X',
        'reasoning' => 'Y',
    ]);

    $result = (new DeleteDecision)($decision);

    expect($result)->toBeTrue();
    expect(Decision::count())->toBe(0);
});
