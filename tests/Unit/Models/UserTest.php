<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

// ============================================================================
// Relationships
// ============================================================================

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

test('user has many conversations', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $project->id,
        'user_id' => $user->id,
        'title' => 'First conversation',
    ]);

    Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $project->id,
        'user_id' => $user->id,
        'title' => 'Second conversation',
    ]);

    expect($user->conversations)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(Conversation::class);
});

test('user without projects returns empty collection', function (): void {
    $user = User::factory()->create();

    expect($user->projects)->toHaveCount(0);
});

test('user without conversations returns empty collection', function (): void {
    $user = User::factory()->create();

    expect($user->conversations)->toHaveCount(0);
});

// ============================================================================
// Casts
// ============================================================================

test('email_verified_at is cast to datetime', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    expect($user->email_verified_at)->toBeInstanceOf(\DateTimeInterface::class);
});

test('password is hashed', function (): void {
    $user = User::factory()->create(['password' => 'plain-password']);

    expect($user->password)->not->toBe('plain-password');
});

// ============================================================================
// Hidden Attributes
// ============================================================================

test('sensitive attributes are hidden from serialization', function (): void {
    $user = User::factory()->create();

    $serialized = $user->toArray();

    expect($serialized)
        ->not->toHaveKey('password')
        ->not->toHaveKey('remember_token')
        ->not->toHaveKey('two_factor_secret')
        ->not->toHaveKey('two_factor_recovery_codes');
});
