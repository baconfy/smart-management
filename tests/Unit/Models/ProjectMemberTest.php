<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

// ============================================================================
// Member Creation
// ============================================================================

test('database assigns default role when not specified', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $member = $project->members()->create(['user_id' => $user->id]);
    $member->refresh();

    expect($member)
        ->toBeInstanceOf(ProjectMember::class)
        ->role->toBe('member');
});

test('can create a project member with specific role', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $member = $project->members()->create([
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    expect($member->role)->toBe('owner');
});

// ============================================================================
// Relationships
// ============================================================================

test('member belongs to a project', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $member = $project->members()->create(['user_id' => $user->id, 'role' => 'member']);

    expect($member->project)
        ->toBeInstanceOf(Project::class)
        ->id->toBe($project->id);
});

test('member belongs to a user', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $member = $project->members()->create(['user_id' => $user->id, 'role' => 'member']);

    expect($member->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

// ============================================================================
// Cascade Delete
// ============================================================================

test('members are deleted when project is deleted', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $project->delete();

    expect(ProjectMember::count())->toBe(0);
});

test('members are deleted when user is deleted', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $user->delete();

    expect(ProjectMember::count())->toBe(0);
});
