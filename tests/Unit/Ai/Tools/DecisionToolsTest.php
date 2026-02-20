<?php

declare(strict_types=1);

use App\Ai\Tools\CreateDecision;
use App\Ai\Tools\ListDecisions;
use App\Ai\Tools\UpdateDecision;
use App\Enums\DecisionStatus;
use App\Models\Decision;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

// ============================================================================
// CreateDecision
// ============================================================================

test('create decision tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = app()->make(CreateDecision::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('create decision tool creates a decision record', function (): void {
    $project = Project::create(['name' => 'Test']);
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
    $project = Project::create(['name' => 'Test']);
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
    $projectA = Project::create(['name' => 'Project A']);
    $projectB = Project::create(['name' => 'Project B']);

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

// ============================================================================
// ListDecisions
// ============================================================================

test('list decisions tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = new ListDecisions($project);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('list decisions tool returns project decisions', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->decisions()->create([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL',
        'reasoning' => 'Best fit.',
    ]);

    $project->decisions()->create([
        'title' => 'Use Redis',
        'choice' => 'Redis',
        'reasoning' => 'Fast caching.',
    ]);

    $tool = new ListDecisions($project);
    $result = (string) $tool->handle(new Request([]));

    expect($result)
        ->toContain('Use PostgreSQL')
        ->toContain('Use Redis');
});

test('list decisions tool filters by status', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->decisions()->create([
        'title' => 'Active Decision',
        'choice' => 'Choice',
        'reasoning' => 'Reason',
        'status' => DecisionStatus::Active->value,
    ]);

    $project->decisions()->create([
        'title' => 'Superseded Decision',
        'choice' => 'Old Choice',
        'reasoning' => 'Old Reason',
        'status' => DecisionStatus::Superseded->value,
    ]);

    $tool = new ListDecisions($project);

    $activeOnly = (string) $tool->handle(new Request(['status' => 'active']));

    expect($activeOnly)
        ->toContain('Active Decision')
        ->not->toContain('Superseded Decision');
});

test('list decisions tool returns all statuses by default', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->decisions()->create([
        'title' => 'Active',
        'choice' => 'A',
        'reasoning' => 'R',
        'status' => DecisionStatus::Active->value,
    ]);

    $project->decisions()->create([
        'title' => 'Superseded',
        'choice' => 'B',
        'reasoning' => 'R',
        'status' => DecisionStatus::Superseded->value,
    ]);

    $tool = new ListDecisions($project);
    $result = (string) $tool->handle(new Request([]));

    expect($result)
        ->toContain('Active')
        ->toContain('Superseded');
});

test('list decisions tool returns message when no decisions exist', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = new ListDecisions($project);

    $result = (string) $tool->handle(new Request([]));

    expect($result)->toContain('No decisions');
});

test('list decisions tool only returns decisions from its project', function (): void {
    $projectA = Project::create(['name' => 'Project A']);
    $projectB = Project::create(['name' => 'Project B']);

    $projectA->decisions()->create([
        'title' => 'Decision A',
        'choice' => 'A',
        'reasoning' => 'R',
    ]);

    $projectB->decisions()->create([
        'title' => 'Decision B',
        'choice' => 'B',
        'reasoning' => 'R',
    ]);

    $tool = new ListDecisions($projectA);
    $result = (string) $tool->handle(new Request([]));

    expect($result)
        ->toContain('Decision A')
        ->not->toContain('Decision B');
});

// ============================================================================
// UpdateDecision
// ============================================================================

test('update decision tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = app()->make(UpdateDecision::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('update decision tool updates a decision', function (): void {
    $project = Project::create(['name' => 'Test']);

    $decision = $project->decisions()->create([
        'title' => 'Use MySQL',
        'choice' => 'MySQL',
        'reasoning' => 'Simple.',
    ]);

    $tool = app()->make(UpdateDecision::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'decision_id' => $decision->id,
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL over MySQL',
        'reasoning' => 'Better JSON support.',
    ]));

    $decision->refresh();

    expect($decision)
        ->title->toBe('Use PostgreSQL')
        ->choice->toBe('PostgreSQL over MySQL')
        ->reasoning->toBe('Better JSON support.');

    expect($result)->toContain('Use PostgreSQL');
});

test('update decision tool can change status', function (): void {
    $project = Project::create(['name' => 'Test']);

    $decision = $project->decisions()->create([
        'title' => 'Use MySQL',
        'choice' => 'MySQL',
        'reasoning' => 'Simple.',
    ]);

    $tool = app()->make(UpdateDecision::class, ['project' => $project]);

    $tool->handle(new Request([
        'decision_id' => $decision->id,
        'status' => 'superseded',
    ]));

    expect($decision->refresh()->status)->toBe(DecisionStatus::Superseded);
});

test('update decision tool only updates provided fields', function (): void {
    $project = Project::create(['name' => 'Test']);

    $decision = $project->decisions()->create([
        'title' => 'Use Redis',
        'choice' => 'Redis',
        'reasoning' => 'Fast.',
    ]);

    $tool = app()->make(UpdateDecision::class, ['project' => $project]);

    $tool->handle(new Request([
        'decision_id' => $decision->id,
        'reasoning' => 'Fast and reliable.',
    ]));

    $decision->refresh();

    expect($decision)
        ->title->toBe('Use Redis')
        ->choice->toBe('Redis')
        ->reasoning->toBe('Fast and reliable.');
});

test('update decision tool scopes to the given project', function (): void {
    $projectA = Project::create(['name' => 'Project A']);
    $projectB = Project::create(['name' => 'Project B']);

    $decision = $projectB->decisions()->create([
        'title' => 'Other Project Decision',
        'choice' => 'X',
        'reasoning' => 'Y',
    ]);

    $tool = app()->make(UpdateDecision::class, ['project' => $projectA]);

    $result = (string) $tool->handle(new Request([
        'decision_id' => $decision->id,
        'title' => 'Hacked',
    ]));

    expect($result)->toContain('not found');
    expect($decision->refresh()->title)->toBe('Other Project Decision');
});
