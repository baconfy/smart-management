<?php

declare(strict_types=1);

use App\Ai\Tools\RemoveBusinessRule;
use App\Models\BusinessRule;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

test('remove business rule tool has a description', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(RemoveBusinessRule::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('remove business rule tool removes a record', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $rule = $project->businessRules()->create([
        'title' => 'Payment must be confirmed within 24h',
        'description' => 'If payment is not confirmed within 24 hours, the invoice expires.',
        'category' => 'payments',
    ]);

    $tool = app()->make(RemoveBusinessRule::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'business_rule_id' => $rule->id,
    ]));

    expect(BusinessRule::count())->toBe(0);
    expect($result)->toContain('Payment must be confirmed within 24h');
    expect($result)->toContain('removed');
});

test('remove business rule tool returns not found for missing rule', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(RemoveBusinessRule::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'business_rule_id' => 999,
    ]));

    expect($result)->toContain('not found');
});

test('remove business rule tool scopes to the given project', function (): void {
    $projectA = Project::factory()->create(['name' => 'Project A']);
    $projectB = Project::factory()->create(['name' => 'Project B']);

    $rule = $projectB->businessRules()->create([
        'title' => 'Other Project Rule',
        'description' => 'D',
        'category' => 'c',
    ]);

    $tool = app()->make(RemoveBusinessRule::class, ['project' => $projectA]);

    $result = (string) $tool->handle(new Request([
        'business_rule_id' => $rule->id,
    ]));

    expect($result)->toContain('not found');
    expect(BusinessRule::count())->toBe(1);
});
