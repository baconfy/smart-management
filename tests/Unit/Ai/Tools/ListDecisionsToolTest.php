<?php

declare(strict_types=1);

use App\Ai\Tools\ListDecisions;
use App\Enums\DecisionStatus;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

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
