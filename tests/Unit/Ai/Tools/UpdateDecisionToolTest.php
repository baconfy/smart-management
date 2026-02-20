<?php

declare(strict_types=1);

use App\Ai\Tools\UpdateDecision;
use App\Enums\DecisionStatus;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

test('update decision tool has a description', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(UpdateDecision::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('update decision tool updates a decision', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

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
    $project = Project::factory()->create(['name' => 'Test']);

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
    $project = Project::factory()->create(['name' => 'Test']);

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
    $projectA = Project::factory()->create(['name' => 'Project A']);
    $projectB = Project::factory()->create(['name' => 'Project B']);

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
