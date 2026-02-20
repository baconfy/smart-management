<?php

declare(strict_types=1);

use App\Ai\Tools\CreateDecision;
use App\Enums\DecisionStatus;
use App\Models\Decision;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

test('create decision tool has a description', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(CreateDecision::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('create decision tool creates a decision record', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(CreateDecision::class, ['project' => $project]);

    $request = new Request([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL over MySQL',
        'reasoning' => 'Better JSON support and ACID compliance.',
    ]);

    $result = (string) $tool->handle($request);

    expect(Decision::count())->toBe(1);

    $decision = Decision::first();

    expect($decision)
        ->project_id->toBe($project->id)
        ->title->toBe('Use PostgreSQL')
        ->choice->toBe('PostgreSQL over MySQL')
        ->reasoning->toBe('Better JSON support and ACID compliance.')
        ->status->toBe(DecisionStatus::Active);

    expect($result)->toContain('Use PostgreSQL');
});

test('create decision tool accepts optional fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(CreateDecision::class, ['project' => $project]);

    $request = new Request([
        'title' => 'Use Redis',
        'choice' => 'Redis for caching',
        'reasoning' => 'Fast in-memory store.',
        'alternatives_considered' => ['Memcached', 'DynamoDB'],
        'context' => 'We need a caching layer for API responses.',
    ]);

    $tool->handle($request);

    $decision = Decision::first();

    expect($decision)
        ->alternatives_considered->toBe(['Memcached', 'DynamoDB'])
        ->context->toBe('We need a caching layer for API responses.');
});

test('create decision tool scopes to the given project', function (): void {
    $projectA = Project::factory()->create(['name' => 'Project A']);
    $projectB = Project::factory()->create(['name' => 'Project B']);

    $toolA = app()->make(CreateDecision::class, ['project' => $projectA]);
    $toolB = app()->make(CreateDecision::class, ['project' => $projectB]);

    $toolA->handle(new Request([
        'title' => 'Decision A',
        'choice' => 'Choice A',
        'reasoning' => 'Reason A',
    ]));

    $toolB->handle(new Request([
        'title' => 'Decision B',
        'choice' => 'Choice B',
        'reasoning' => 'Reason B',
    ]));

    expect($projectA->decisions)->toHaveCount(1);
    expect($projectB->decisions)->toHaveCount(1);
    expect($projectA->decisions->first()->title)->toBe('Decision A');
});
