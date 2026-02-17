<?php

declare(strict_types=1);

use App\Enums\BusinessRuleStatus;
use App\Enums\DecisionStatus;
use App\Models\BusinessRule;
use App\Models\Decision;
use App\Models\Project;

// ============================================================================
// Decision Creation
// ============================================================================

test('can create a decision with required fields', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $decision = $project->decisions()->create([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL',
        'reasoning' => 'Better support for JSON, extensions, and concurrent writes.',
        'status' => DecisionStatus::Active->value,
    ]);

    expect($decision)
        ->toBeInstanceOf(Decision::class)
        ->title->toBe('Use PostgreSQL')
        ->choice->toBe('PostgreSQL')
        ->status->toBe(DecisionStatus::Active);
});

test('decision stores alternatives as json', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $alternatives = ['MySQL', 'SQLite', 'MongoDB'];

    $decision = $project->decisions()->create([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL',
        'reasoning' => 'Best fit for our needs.',
        'alternatives_considered' => $alternatives,
        'status' => DecisionStatus::Active->value,
    ]);

    expect($decision->alternatives_considered)
        ->toBeArray()
        ->toBe($alternatives);
});

test('decision has nullable context and conversation_message_id', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $decision = $project->decisions()->create([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL',
        'reasoning' => 'Best fit.',
        'status' => DecisionStatus::Active->value,
    ]);

    expect($decision)
        ->context->toBeNull()
        ->conversation_message_id->toBeNull();
});

// ============================================================================
// Decision Relationships
// ============================================================================

test('decision belongs to project', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $decision = $project->decisions()->create([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL',
        'reasoning' => 'Best fit.',
        'status' => DecisionStatus::Active->value,
    ]);

    expect($decision->project)
        ->toBeInstanceOf(Project::class)
        ->id->toBe($project->id);
});

test('project has many decisions', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $project->decisions()->create([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL',
        'reasoning' => 'Best fit.',
        'status' => DecisionStatus::Active->value,
    ]);

    $project->decisions()->create([
        'title' => 'Use Laravel',
        'choice' => 'Laravel',
        'reasoning' => 'Team expertise.',
        'status' => DecisionStatus::Active->value,
    ]);

    expect($project->decisions)->toHaveCount(2);
});

// ============================================================================
// Decision Scopes
// ============================================================================

test('active scope filters decisions', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $project->decisions()->create([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL',
        'reasoning' => 'Best fit.',
        'status' => DecisionStatus::Active->value,
    ]);

    $project->decisions()->create([
        'title' => 'Use MySQL',
        'choice' => 'MySQL',
        'reasoning' => 'Was cheaper.',
        'status' => DecisionStatus::Superseded->value,
    ]);

    expect($project->decisions()->active()->get())->toHaveCount(1);
});

// ============================================================================
// Decision Status Enum
// ============================================================================

test('decision status is cast to enum', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $decision = $project->decisions()->create([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL',
        'reasoning' => 'Best fit.',
        'status' => 'active',
    ]);

    expect($decision->status)->toBeInstanceOf(DecisionStatus::class);
});

test('all decision statuses are valid', function (): void {
    $expected = ['active', 'superseded', 'deprecated'];

    $values = array_map(fn (DecisionStatus $s) => $s->value, DecisionStatus::cases());

    expect($values)->toBe($expected);
});

// ============================================================================
// BusinessRule Creation
// ============================================================================

test('can create a business rule with required fields', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $rule = $project->businessRules()->create([
        'title' => 'Non-custodial gateway',
        'description' => 'The gateway never holds user funds. Split is immediate.',
        'category' => 'Payments',
        'status' => BusinessRuleStatus::Active->value,
    ]);

    expect($rule)
        ->toBeInstanceOf(BusinessRule::class)
        ->title->toBe('Non-custodial gateway')
        ->category->toBe('Payments')
        ->status->toBe(BusinessRuleStatus::Active);
});

// ============================================================================
// BusinessRule Relationships
// ============================================================================

test('business rule belongs to project', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $rule = $project->businessRules()->create([
        'title' => 'Non-custodial',
        'description' => 'Never holds funds.',
        'category' => 'Payments',
        'status' => BusinessRuleStatus::Active->value,
    ]);

    expect($rule->project)
        ->toBeInstanceOf(Project::class)
        ->id->toBe($project->id);
});

test('project has many business rules', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $project->businessRules()->create([
        'title' => 'Non-custodial',
        'description' => 'Never holds funds.',
        'category' => 'Payments',
        'status' => BusinessRuleStatus::Active->value,
    ]);

    $project->businessRules()->create([
        'title' => 'Fee is 0.5%',
        'description' => 'Merchant pays 0.5% fee on each transaction.',
        'category' => 'Payments',
        'status' => BusinessRuleStatus::Active->value,
    ]);

    expect($project->businessRules)->toHaveCount(2);
});

// ============================================================================
// BusinessRule Scopes
// ============================================================================

test('active scope filters business rules', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $project->businessRules()->create([
        'title' => 'Non-custodial',
        'description' => 'Never holds funds.',
        'category' => 'Payments',
        'status' => BusinessRuleStatus::Active->value,
    ]);

    $project->businessRules()->create([
        'title' => 'Old rule',
        'description' => 'No longer applies.',
        'category' => 'Legacy',
        'status' => BusinessRuleStatus::Deprecated->value,
    ]);

    expect($project->businessRules()->active()->get())->toHaveCount(1);
});

// ============================================================================
// BusinessRule Status Enum
// ============================================================================

test('business rule status is cast to enum', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $rule = $project->businessRules()->create([
        'title' => 'Non-custodial',
        'description' => 'Never holds funds.',
        'category' => 'Payments',
        'status' => 'active',
    ]);

    expect($rule->status)->toBeInstanceOf(BusinessRuleStatus::class);
});

test('all business rule statuses are valid', function (): void {
    $expected = ['active', 'deprecated'];

    $values = array_map(fn (BusinessRuleStatus $s) => $s->value, BusinessRuleStatus::cases());

    expect($values)->toBe($expected);
});

// ============================================================================
// Cascade Delete
// ============================================================================

test('decisions are deleted when project is deleted', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $project->decisions()->create([
        'title' => 'Use PostgreSQL',
        'choice' => 'PostgreSQL',
        'reasoning' => 'Best fit.',
        'status' => DecisionStatus::Active->value,
    ]);

    $project->delete();

    expect(Decision::count())->toBe(0);
});

test('business rules are deleted when project is deleted', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $project->businessRules()->create([
        'title' => 'Non-custodial',
        'description' => 'Never holds funds.',
        'category' => 'Payments',
        'status' => BusinessRuleStatus::Active->value,
    ]);

    $project->delete();

    expect(BusinessRule::count())->toBe(0);
});
