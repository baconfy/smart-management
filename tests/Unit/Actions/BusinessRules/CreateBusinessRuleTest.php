<?php

declare(strict_types=1);

use App\Actions\BusinessRules\CreateBusinessRule;
use App\Enums\BusinessRuleStatus;
use App\Models\BusinessRule;
use App\Models\Project;

test('it creates a business rule for a project', function (): void {
    $project = Project::create(['name' => 'Test']);

    $rule = (new CreateBusinessRule)($project, [
        'title' => 'Max discount 20%',
        'description' => 'Discounts cannot exceed 20% of the original price.',
        'category' => 'billing',
    ]);

    expect($rule)
        ->toBeInstanceOf(BusinessRule::class)
        ->project_id->toBe($project->id)
        ->title->toBe('Max discount 20%')
        ->description->toBe('Discounts cannot exceed 20% of the original price.')
        ->category->toBe('billing');

    expect($rule->refresh()->status)->toBe(BusinessRuleStatus::Active);
});

test('it scopes to the given project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);

    (new CreateBusinessRule)($projectA, ['title' => 'Rule A', 'description' => 'D', 'category' => 'billing']);
    (new CreateBusinessRule)($projectB, ['title' => 'Rule B', 'description' => 'D', 'category' => 'security']);

    expect($projectA->businessRules)->toHaveCount(1);
    expect($projectA->businessRules->first()->title)->toBe('Rule A');
});
