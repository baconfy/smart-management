<?php

declare(strict_types=1);

use App\Enums\AgentType;
use App\Models\Project;
use App\Models\ProjectAgent;

// ============================================================================
// Agent Creation
// ============================================================================

test('can create a project agent with required fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a software architect.',
    ]);

    expect($agent)
        ->toBeInstanceOf(ProjectAgent::class)
        ->name->toBe('Architect')
        ->type->toBe(AgentType::Architect)
        ->instructions->toBe('You are a software architect.');
});

test('agent has nullable optional fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $agent = $project->agents()->create([
        'type' => AgentType::Analyst->value,
        'name' => 'Analyst',
        'instructions' => 'You are an analyst.',
    ]);

    expect($agent)
        ->model->toBeNull()
        ->settings->toBeNull()
        ->tools->toBeNull();
});

// ============================================================================
// Casts
// ============================================================================

test('type is cast to AgentType enum', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $agent = $project->agents()->create([
        'type' => 'architect',
        'name' => 'Architect',
        'instructions' => 'You are a software architect.',
    ]);

    expect($agent->type)->toBeInstanceOf(AgentType::class)
        ->and($agent->type)->toBe(AgentType::Architect);
});

test('is_default is cast to boolean', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $agent = $project->agents()->create([
        'type' => AgentType::Pm->value,
        'name' => 'PM',
        'instructions' => 'You are a project manager.',
        'is_default' => true,
    ]);

    expect($agent->is_default)->toBeTrue()->toBeBool();
});

test('settings are cast to array', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $settings = ['temperature' => 0.7, 'max_tokens' => 4096];

    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a software architect.',
        'settings' => $settings,
    ]);

    expect($agent->settings)
        ->toBeArray()
        ->toBe($settings);
});

test('tools are cast to array', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $tools = ['CreateTask', 'UpdateTask', 'ListTasks'];

    $agent = $project->agents()->create([
        'type' => AgentType::Pm->value,
        'name' => 'PM',
        'instructions' => 'You are a project manager.',
        'tools' => $tools,
    ]);

    expect($agent->tools)
        ->toBeArray()
        ->toBe($tools);
});

// ============================================================================
// All Enum Values
// ============================================================================

test('all agent types are valid', function (): void {
    $expected = ['architect', 'analyst', 'pm', 'dba', 'technical', 'custom'];

    $values = array_map(fn (AgentType $t) => $t->value, AgentType::cases());

    expect($values)->toBe($expected);
});

// ============================================================================
// Scopes
// ============================================================================

test('defaults scope filters only default agents', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a software architect.',
        'is_default' => true,
    ]);

    $project->agents()->create([
        'type' => AgentType::Pm->value,
        'name' => 'PM',
        'instructions' => 'You are a project manager.',
        'is_default' => true,
    ]);

    $project->agents()->create([
        'type' => AgentType::Custom->value,
        'name' => 'Custom Agent',
        'instructions' => 'Custom instructions.',
        'is_default' => false,
    ]);

    expect($project->agents()->defaults()->get())->toHaveCount(2);
});

test('defaults scope excludes custom agents', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $project->agents()->create([
        'type' => AgentType::Custom->value,
        'name' => 'Custom Agent',
        'instructions' => 'Custom instructions.',
        'is_default' => false,
    ]);

    expect($project->agents()->defaults()->get())->toHaveCount(0);
});

// ============================================================================
// Relationships
// ============================================================================

test('agent belongs to a project', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a software architect.',
    ]);

    expect($agent->project)
        ->toBeInstanceOf(Project::class)
        ->id->toBe($project->id);
});

test('project has many agents', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a software architect.',
    ]);

    $project->agents()->create([
        'type' => AgentType::Pm->value,
        'name' => 'PM',
        'instructions' => 'You are a project manager.',
    ]);

    expect($project->agents)->toHaveCount(2);
});

// ============================================================================
// Cascade Soft Delete
// ============================================================================

test('agents are soft deleted when project is deleted', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a software architect.',
    ]);

    $project->delete();

    expect(ProjectAgent::count())->toBe(0)
        ->and(ProjectAgent::withTrashed()->count())->toBe(1);
});

// ============================================================================
// Cascade Restore
// ============================================================================

test('agents are restored when project is restored', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a software architect.',
    ]);

    $project->delete();
    $project->restore();

    expect(ProjectAgent::count())->toBe(1);
});

// ============================================================================
// Cascade Force Delete
// ============================================================================

test('agents are force deleted when project is force deleted', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a software architect.',
    ]);

    $project->forceDelete();

    expect(ProjectAgent::withTrashed()->count())->toBe(0);
});
