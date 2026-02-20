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
    $project = Project::factory()->create(['name' => 'Test Project']);

    $member = $project->members()->create(['user_id' => $user->id]);
    $member->refresh();

    expect($member)
        ->toBeInstanceOf(ProjectMember::class)
        ->role->toBe('member');
});

test('can create a project member with specific role', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test Project']);

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
    $project = Project::factory()->create(['name' => 'Test Project']);

    $member = $project->members()->create(['user_id' => $user->id, 'role' => 'member']);

    expect($member->project)
        ->toBeInstanceOf(Project::class)
        ->id->toBe($project->id);
});

test('member belongs to a user', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test Project']);

    $member = $project->members()->create(['user_id' => $user->id, 'role' => 'member']);

    expect($member->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

// ============================================================================
// Cascade Soft Delete
// ============================================================================

test('members are soft deleted when project is deleted', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test Project']);

    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $project->delete();

    expect(ProjectMember::count())->toBe(0)
        ->and(ProjectMember::withTrashed()->count())->toBe(1);
});

test('members are force deleted when user is deleted', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test Project']);

    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $user->delete();

    expect(ProjectMember::withTrashed()->count())->toBe(0);
});

// ============================================================================
// Cascade Restore
// ============================================================================

test('members are restored when project is restored', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test Project']);

    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $project->delete();
    $project->restore();

    expect(ProjectMember::count())->toBe(1);
});

// ============================================================================
// Cascade Force Delete
// ============================================================================

test('members are force deleted when project is force deleted', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test Project']);

    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $project->forceDelete();

    expect(ProjectMember::withTrashed()->count())->toBe(0);
});
