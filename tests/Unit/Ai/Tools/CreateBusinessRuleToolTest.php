<?php

declare(strict_types=1);

use App\Ai\Tools\CreateBusinessRule;
use App\Enums\BusinessRuleStatus;
use App\Models\BusinessRule;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

test('create business rule tool has a description', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(CreateBusinessRule::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('create business rule tool creates a record', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $tool = app()->make(CreateBusinessRule::class, ['project' => $project]);

    $result = (string) $tool->handle(new Request([
        'title' => 'Payment must be confirmed within 24h',
        'description' => 'If payment is not confirmed within 24 hours, the invoice expires.',
        'category' => 'payments',
    ]));

    expect(BusinessRule::count())->toBe(1);

    $rule = BusinessRule::first();

    expect($rule)
        ->project_id->toBe($project->id)
        ->title->toBe('Payment must be confirmed within 24h')
        ->description->toBe('If payment is not confirmed within 24 hours, the invoice expires.')
        ->category->toBe('payments')
        ->status->toBe(BusinessRuleStatus::Active);

    expect($result)->toContain('Payment must be confirmed within 24h');
});

test('create business rule tool scopes to the given project', function (): void {
    $projectA = Project::factory()->create(['name' => 'A']);
    $projectB = Project::factory()->create(['name' => 'B']);

    app()->make(CreateBusinessRule::class, ['project' => $projectA])->handle(new Request(['title' => 'Rule A', 'description' => 'Desc', 'category' => 'general']));
    app()->make(CreateBusinessRule::class, ['project' => $projectB])->handle(new Request(['title' => 'Rule B', 'description' => 'Desc', 'category' => 'general']));

    expect($projectA->businessRules)->toHaveCount(1);
    expect($projectB->businessRules)->toHaveCount(1);
});
