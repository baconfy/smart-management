<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

// ============================================================================
// Conversation Creation
// ============================================================================

test('can create a conversation with required fields', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Defining the stack',
    ]);

    expect($conversation)
        ->toBeInstanceOf(Conversation::class)
        ->title->toBe('Defining the stack');
});

test('conversation uses string primary key', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);
    $ulid = (string) Str::ulid();

    $conversation = Conversation::create([
        'id' => $ulid,
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    expect($conversation->id)->toBe($ulid)
        ->and($conversation->incrementing)->toBeFalse()
        ->and($conversation->getKeyType())->toBe('string');
});

// ============================================================================
// Relationships
// ============================================================================

test('conversation belongs to a project', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    expect($conversation->project)
        ->toBeInstanceOf(Project::class)
        ->id->toBe($project->id);
});

test('conversation belongs to a user', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    expect($conversation->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});
