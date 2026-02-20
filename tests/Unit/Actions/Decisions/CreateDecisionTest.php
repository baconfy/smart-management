<?php

declare(strict_types=1);

use App\Actions\Decisions\CreateDecision;
use App\Enums\DecisionStatus;
use App\Models\Decision;
use App\Models\Project;

test('it creates a decision for a project', function (): void {
    $project = Project::create(['name' => 'Test']);

    $decision = (new CreateDecision)($project, [
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL over MySQL',
        'reasoning' => 'Better JSON support.',
    ]);

    expect($decision)
        ->toBeInstanceOf(Decision::class)
        ->project_id->toBe($project->id)
        ->title->toBe('Use PostgreSQL')
        ->choice->toBe('PostgreSQL over MySQL')
        ->reasoning->toBe('Better JSON support.');

    expect($decision->refresh()->status)->toBe(DecisionStatus::Active);
});

test('it accepts optional fields', function (): void {
    $project = Project::create(['name' => 'Test']);

    $decision = (new CreateDecision)($project, [
        'title' => 'Use Redis',
        'choice' => 'Redis for caching',
        'reasoning' => 'Fast in-memory store.',
        'alternatives_considered' => ['Memcached', 'DynamoDB'],
        'context' => 'Need caching for API responses.',
    ]);

    expect($decision)
        ->alternatives_considered->toBe(['Memcached', 'DynamoDB'])
        ->context->toBe('Need caching for API responses.');
});

test('it scopes to the given project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);

    (new CreateDecision)($projectA, ['title' => 'Decision A', 'choice' => 'A', 'reasoning' => 'R']);
    (new CreateDecision)($projectB, ['title' => 'Decision B', 'choice' => 'B', 'reasoning' => 'R']);

    expect($projectA->decisions)->toHaveCount(1);
    expect($projectA->decisions->first()->title)->toBe('Decision A');
});
