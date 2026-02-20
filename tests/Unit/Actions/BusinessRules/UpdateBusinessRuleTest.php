<?php

declare(strict_types=1);

use App\Actions\BusinessRules\UpdateBusinessRule;
use App\Enums\BusinessRuleStatus;
use App\Models\BusinessRule;
use App\Models\Project;

test('it updates a business rule', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $rule = $project->businessRules()->create([
        'title' => 'Old Title',
        'description' => 'Old description',
        'category' => 'billing',
    ]);

    $result = (new UpdateBusinessRule)($rule, [
        'title' => 'New Title',
        'description' => 'New description',
    ]);

    expect($result)
        ->toBeInstanceOf(BusinessRule::class)
        ->title->toBe('New Title')
        ->description->toBe('New description');
});

test('it can change status', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $rule = $project->businessRules()->create([
        'title' => 'Rule',
        'description' => 'D',
        'category' => 'billing',
    ]);

    (new UpdateBusinessRule)($rule, ['status' => 'deprecated']);

    expect($rule->refresh()->status)->toBe(BusinessRuleStatus::Deprecated);
});

test('it preserves unchanged fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $rule = $project->businessRules()->create([
        'title' => 'Keep',
        'description' => 'Original',
        'category' => 'security',
    ]);

    (new UpdateBusinessRule)($rule, ['description' => 'Updated']);

    $rule->refresh();

    expect($rule)
        ->title->toBe('Keep')
        ->description->toBe('Updated')
        ->category->toBe('security');
});
