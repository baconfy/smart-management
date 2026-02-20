<?php

declare(strict_types=1);

use App\Ai\Tools\UpdateBusinessRule;
use App\Enums\BusinessRuleStatus;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

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
