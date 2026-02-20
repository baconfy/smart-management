<?php

declare(strict_types=1);

use App\Ai\Tools\RemoveDecision;
use App\Models\Decision;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

test('remove decision tool has a description', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(RemoveDecision::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('remove decision tool removes a decision', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $decision = $project->decisions()->create([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL',
        'reasoning' => 'Best fit.',
    ]);

    $tool = app()->make(RemoveDecision::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'decision_id' => $decision->id,
    ]));

    expect(Decision::count())->toBe(0);
    expect($result)->toContain('Use PostgreSQL');
    expect($result)->toContain('removed');
});

test('remove decision tool returns not found for missing decision', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(RemoveDecision::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'decision_id' => 999,
    ]));

    expect($result)->toContain('not found');
});

test('remove decision tool scopes to the given project', function (): void {
    $projectA = Project::factory()->create(['name' => 'Project A']);
    $projectB = Project::factory()->create(['name' => 'Project B']);

    $decision = $projectB->decisions()->create([
        'title' => 'Other Project Decision',
        'choice' => 'X',
        'reasoning' => 'Y',
    ]);

    $tool = app()->make(RemoveDecision::class, ['project' => $projectA]);

    $result = (string) $tool->handle(new Request([
        'decision_id' => $decision->id,
    ]));

    expect($result)->toContain('not found');
    expect(Decision::count())->toBe(1);
});
