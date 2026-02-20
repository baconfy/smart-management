<?php

declare(strict_types=1);

use App\Ai\Tools\CreateBusinessRule;
use App\Ai\Tools\ListBusinessRules;
use App\Ai\Tools\UpdateBusinessRule;
use App\Enums\BusinessRuleStatus;
use App\Models\BusinessRule;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

// ============================================================================
// CreateBusinessRule
// ============================================================================

test('create business rule tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = app()->make(CreateBusinessRule::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('create business rule tool creates a record', function (): void {
    $project = Project::create(['name' => 'Test']);
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
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);

    app()->make(CreateBusinessRule::class, ['project' => $projectA])->handle(new Request(['title' => 'Rule A', 'description' => 'Desc', 'category' => 'general']));
    app()->make(CreateBusinessRule::class, ['project' => $projectB])->handle(new Request(['title' => 'Rule B', 'description' => 'Desc', 'category' => 'general']));

    expect($projectA->businessRules)->toHaveCount(1);
    expect($projectB->businessRules)->toHaveCount(1);
});

// ============================================================================
// ListBusinessRules
// ============================================================================

test('list business rules tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = new ListBusinessRules($project);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('list business rules tool returns project rules', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->businessRules()->create(['title' => 'Rule 1', 'description' => 'Desc 1', 'category' => 'payments']);
    $project->businessRules()->create(['title' => 'Rule 2', 'description' => 'Desc 2', 'category' => 'auth']);

    $result = (string) new ListBusinessRules($project)->handle(new Request([]));

    expect($result)->toContain('Rule 1')->toContain('Rule 2');
});

test('list business rules tool filters by status', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->businessRules()->create(['title' => 'Active Rule', 'description' => 'D', 'category' => 'c', 'status' => 'active']);
    $project->businessRules()->create(['title' => 'Deprecated Rule', 'description' => 'D', 'category' => 'c', 'status' => 'deprecated']);

    $result = (string) new ListBusinessRules($project)->handle(new Request(['status' => 'active']));

    expect($result)->toContain('Active Rule')->not->toContain('Deprecated Rule');
});

test('list business rules tool filters by category', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->businessRules()->create(['title' => 'Payment Rule', 'description' => 'D', 'category' => 'payments']);
    $project->businessRules()->create(['title' => 'Auth Rule', 'description' => 'D', 'category' => 'auth']);

    $result = (string) new ListBusinessRules($project)->handle(new Request(['category' => 'payments']));

    expect($result)->toContain('Payment Rule')->not->toContain('Auth Rule');
});

test('list business rules tool returns message when empty', function (): void {
    $project = Project::create(['name' => 'Test']);

    $result = (string) new ListBusinessRules($project)->handle(new Request([]));

    expect($result)->toContain('No business rules');
});

test('list business rules tool only returns rules from its project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);

    $projectA->businessRules()->create(['title' => 'Rule A', 'description' => 'D', 'category' => 'c']);
    $projectB->businessRules()->create(['title' => 'Rule B', 'description' => 'D', 'category' => 'c']);

    $result = (string) new ListBusinessRules($projectA)->handle(new Request([]));

    expect($result)->toContain('Rule A')->not->toContain('Rule B');
});

// ============================================================================
// UpdateBusinessRule
// ============================================================================

test('update business rule tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = app()->make(UpdateBusinessRule::class, ['project' => $project]);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('update business rule tool updates a record', function (): void {
    $project = Project::create(['name' => 'Test']);

    $rule = $project->businessRules()->create(['title' => 'Old Title', 'description' => 'Old Desc', 'category' => 'general']);

    $result = (string) app()->make(UpdateBusinessRule::class, ['project' => $project])->handle(new Request([
        'business_rule_id' => $rule->id,
        'title' => 'New Title',
        'description' => 'New Desc',
    ]));

    $rule->refresh();

    expect($rule)->title->toBe('New Title')->description->toBe('New Desc');
    expect($result)->toContain('New Title');
});

test('update business rule tool can change status', function (): void {
    $project = Project::create(['name' => 'Test']);

    $rule = $project->businessRules()->create(['title' => 'Rule', 'description' => 'D', 'category' => 'c']);

    app()->make(UpdateBusinessRule::class, ['project' => $project])->handle(new Request([
        'business_rule_id' => $rule->id,
        'status' => 'deprecated',
    ]));

    expect($rule->refresh()->status)->toBe(BusinessRuleStatus::Deprecated);
});

test('update business rule tool only updates provided fields', function (): void {
    $project = Project::create(['name' => 'Test']);

    $rule = $project->businessRules()->create(['title' => 'Keep', 'description' => 'Keep Desc', 'category' => 'payments']);

    app()->make(UpdateBusinessRule::class, ['project' => $project])->handle(new Request([
        'business_rule_id' => $rule->id,
        'category' => 'auth',
    ]));

    $rule->refresh();

    expect($rule)->title->toBe('Keep')->description->toBe('Keep Desc')->category->toBe('auth');
});

test('update business rule tool scopes to the given project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);

    $rule = $projectB->businessRules()->create(['title' => 'Other', 'description' => 'D', 'category' => 'c']);

    $result = (string) app()->make(UpdateBusinessRule::class, ['project' => $projectA])->handle(new Request([
        'business_rule_id' => $rule->id,
        'title' => 'Hacked',
    ]));

    expect($result)->toContain('not found');
    expect($rule->refresh()->title)->toBe('Other');
});
