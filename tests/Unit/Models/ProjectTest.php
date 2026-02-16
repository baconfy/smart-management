<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ============================================================================
// Project Creation
// ============================================================================

test('can create a project with required fields', function (): void {
    $project = Project::create(['name' => 'Arkham District', 'description' => 'Cryptocurrency payment gateway']);

    expect($project)
        ->toBeInstanceOf(Project::class)
        ->name->toBe('Arkham District')
        ->description->toBe('Cryptocurrency payment gateway')
        ->settings->toBeNull();
});

test('can create a project with settings json', function (): void {
    $settings = ['default_provider' => 'anthropic', 'default_model' => 'claude-sonnet-4-5-20250929'];

    $project = Project::create(['name' => 'Test Project', 'settings' => $settings]);

    expect($project->settings)
        ->toBeArray()
        ->toBe($settings);
});

test('project requires a name', function (): void {
    Project::create(['description' => 'No name']);
})->throws(QueryException::class);

// ============================================================================
// Project Members
// ============================================================================

test('can add a member to a project', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $member = $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    expect($member)
        ->toBeInstanceOf(ProjectMember::class)
        ->role->toBe('owner');
});

test('project has many members', function (): void {
    $project = Project::create(['name' => 'Team Project']);
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();

    $project->members()->create(['user_id' => $owner->id, 'role' => 'owner']);
    $project->members()->create(['user_id' => $collaborator->id, 'role' => 'member']);

    expect($project->members)->toHaveCount(2);
});

test('project has many users through members', function (): void {
    $project = Project::create(['name' => 'Team Project']);
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();

    $project->members()->create(['user_id' => $owner->id, 'role' => 'owner']);
    $project->members()->create(['user_id' => $collaborator->id, 'role' => 'member']);

    expect($project->users)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(User::class);
});

test('user has many projects through members', function (): void {
    $user = User::factory()->create();

    $projectA = Project::create(['name' => 'Project A']);
    $projectB = Project::create(['name' => 'Project B']);

    $projectA->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $projectB->members()->create(['user_id' => $user->id, 'role' => 'member']);

    expect($user->projects)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(Project::class);
});

// ============================================================================
// Ownership
// ============================================================================

test('project owner is the member with owner role', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::create(['name' => 'Owned Project']);

    $project->members()->create(['user_id' => $owner->id, 'role' => 'owner']);
    $project->members()->create(['user_id' => $member->id, 'role' => 'member']);

    expect($project->owner)
        ->toBeInstanceOf(User::class)
        ->id->toBe($owner->id);
});

test('project without owner returns null', function (): void {
    $project = Project::create(['name' => 'Orphan Project']);

    expect($project->owner)->toBeNull();
});

// ============================================================================
// Member Roles
// ============================================================================

test('member belongs to project and user', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $member = $project->members()->create([
        'user_id' => $user->id,
        'role' => 'admin',
    ]);

    expect($member->project)
        ->toBeInstanceOf(Project::class)
        ->id->toBe($project->id);

    expect($member->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

// ============================================================================
// Uniqueness
// ============================================================================

test('user cannot be added to the same project twice', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'member']);
})->throws(QueryException::class);
