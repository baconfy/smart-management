<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use App\Services\CreateProjectService;

// ============================================================================
// Project Creation
// ============================================================================

test('creates a project with name and description', function (): void {
    $user = User::factory()->create();

    $project = app(CreateProjectService::class)($user, ['name' => 'Arkham District', 'description' => 'Cryptocurrency payment gateway']);

    expect($project)
        ->toBeInstanceOf(Project::class)
        ->name->toBe('Arkham District')
        ->description->toBe('Cryptocurrency payment gateway');
});

test('creates a project with minimal data', function (): void {
    $user = User::factory()->create();

    $project = app(CreateProjectService::class)($user, ['name' => 'Minimal Project']);

    expect($project)
        ->name->toBe('Minimal Project')
        ->description->toBeNull();
});

// ============================================================================
// Owner Membership
// ============================================================================

test('adds the creator as owner member', function (): void {
    $user = User::factory()->create();

    $project = app(CreateProjectService::class)($user, ['name' => 'Test Project']);

    expect($project->members)->toHaveCount(1);

    expect($project->members->first())
        ->user_id->toBe($user->id)
        ->role->toBe('owner');
});

// ============================================================================
// Agent Seeding
// ============================================================================

test('seeds all default agents on creation', function (): void {
    $user = User::factory()->create();

    $project = app(CreateProjectService::class)($user, ['name' => 'Test Project']);

    expect($project->agents)->toHaveCount(5);

    $types = $project->agents->pluck('type')->map->value->all();

    expect($types)->toEqualCanonicalizing(['architect', 'analyst', 'pm', 'dba', 'technical']);
});

test('all default agents are marked as is_default', function (): void {
    $user = User::factory()->create();

    $project = app(CreateProjectService::class)($user, ['name' => 'Test Project']);

    expect($project->agents->every(fn ($a) => $a->is_default))->toBeTrue();
});

// ============================================================================
// Instructions from .md files
// ============================================================================

test('agents have instructions loaded from md files', function (): void {
    $user = User::factory()->create();

    $project = app(CreateProjectService::class)($user, ['name' => 'Test Project']);

    $project->agents->each(function ($agent) {
        expect($agent->instructions)
            ->toBeString()
            ->not->toBeEmpty();
    });
});

test('each agent type has distinct instructions', function (): void {
    $user = User::factory()->create();

    $project = app(CreateProjectService::class)($user, ['name' => 'Test Project']);

    $instructions = $project->agents->pluck('instructions')->unique();

    expect($instructions)->toHaveCount(4);
});
