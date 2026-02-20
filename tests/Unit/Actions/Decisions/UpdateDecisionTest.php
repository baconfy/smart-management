<?php

declare(strict_types=1);

use App\Actions\Decisions\UpdateDecision;
use App\Enums\DecisionStatus;
use App\Models\Decision;
use App\Models\Project;

test('it updates a decision', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $decision = $project->decisions()->create([
        'title' => 'Use MySQL',
        'choice' => 'MySQL',
        'reasoning' => 'Simple.',
    ]);

    $result = (new UpdateDecision)($decision, [
        'title' => 'Use PostgreSQL',
        'reasoning' => 'Better JSON support.',
    ]);

    expect($result)
        ->toBeInstanceOf(Decision::class)
        ->title->toBe('Use PostgreSQL')
        ->reasoning->toBe('Better JSON support.');
});

test('it can change status', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $decision = $project->decisions()->create([
        'title' => 'Old Decision',
        'choice' => 'X',
        'reasoning' => 'Y',
    ]);

    (new UpdateDecision)($decision, ['status' => 'superseded']);

    expect($decision->refresh()->status)->toBe(DecisionStatus::Superseded);
});

test('it preserves unchanged fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $decision = $project->decisions()->create([
        'title' => 'Keep',
        'choice' => 'Redis',
        'reasoning' => 'Fast.',
    ]);

    (new UpdateDecision)($decision, ['reasoning' => 'Fast and reliable.']);

    $decision->refresh();

    expect($decision)
        ->title->toBe('Keep')
        ->choice->toBe('Redis')
        ->reasoning->toBe('Fast and reliable.');
});
