<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\ConversationMessage;
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

// ============================================================================
// Cascade Soft Delete
// ============================================================================

test('conversations are soft deleted when project is deleted', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    $project->delete();

    expect(Conversation::count())->toBe(0)
        ->and(Conversation::withTrashed()->count())->toBe(1);
});

test('messages are soft deleted when conversation is deleted', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    ConversationMessage::create([
        'id' => Str::ulid(),
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'role' => 'user',
        'content' => 'Hello',
    ]);

    $conversation->delete();

    expect(ConversationMessage::count())->toBe(0)
        ->and(ConversationMessage::withTrashed()->count())->toBe(1);
});

test('messages are soft deleted via project cascade', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    ConversationMessage::create([
        'id' => Str::ulid(),
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'role' => 'user',
        'content' => 'Hello',
    ]);

    $project->delete();

    expect(Conversation::count())->toBe(0)
        ->and(ConversationMessage::count())->toBe(0)
        ->and(ConversationMessage::withTrashed()->count())->toBe(1);
});

// ============================================================================
// Cascade Restore
// ============================================================================

test('conversations are restored when project is restored', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    $project->delete();
    $project->restore();

    expect(Conversation::count())->toBe(1);
});

test('messages are restored when conversation is restored', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    ConversationMessage::create([
        'id' => Str::ulid(),
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'role' => 'user',
        'content' => 'Hello',
    ]);

    $conversation->delete();
    $conversation->restore();

    expect(ConversationMessage::count())->toBe(1);
});

test('messages are restored via project cascade', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    ConversationMessage::create([
        'id' => Str::ulid(),
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'role' => 'user',
        'content' => 'Hello',
    ]);

    $project->delete();
    $project->restore();

    expect(Conversation::count())->toBe(1)
        ->and(ConversationMessage::count())->toBe(1);
});

// ============================================================================
// Cascade Force Delete
// ============================================================================

test('conversations are force deleted when project is force deleted', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    $project->forceDelete();

    expect(Conversation::withTrashed()->count())->toBe(0);
});

test('messages are force deleted when conversation is force deleted', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    ConversationMessage::create([
        'id' => Str::ulid(),
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'role' => 'user',
        'content' => 'Hello',
    ]);

    $conversation->forceDelete();

    expect(ConversationMessage::withTrashed()->count())->toBe(0);
});

test('messages are force deleted via project cascade', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test Project']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test',
    ]);

    ConversationMessage::create([
        'id' => Str::ulid(),
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'role' => 'user',
        'content' => 'Hello',
    ]);

    $project->forceDelete();

    expect(Conversation::withTrashed()->count())->toBe(0)
        ->and(ConversationMessage::withTrashed()->count())->toBe(0);
});
