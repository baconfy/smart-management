<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

// ============================================================================
// Authorization
// ============================================================================

test('guest cannot view project conversations', function (): void {
    $project = Project::create(['name' => 'Test']);

    $this->get("/projects/{$project->ulid}/conversations")
        ->assertRedirect('/login');
});

test('non-member cannot view project conversations', function (): void {
    $project = Project::create(['name' => 'Test']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get("/projects/{$project->ulid}/conversations")
        ->assertForbidden();
});

// ============================================================================
// Index (New Conversation)
// ============================================================================

test('member can view conversations index', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->get("/projects/{$project->ulid}/conversations")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/conversations/index')
            ->has('project')
            ->has('agents')
        );
});

// ============================================================================
// Show (Existing Conversation)
// ============================================================================

test('member can view a conversation', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Test conversation',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->ulid}/conversations/{$conversation->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/conversations/show')
            ->has('project')
            ->has('agents')
            ->has('conversation')
            ->has('messages')
        );
});

test('non-member cannot view a conversation', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $otherUser->id, 'role' => 'owner']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $otherUser->id,
        'project_id' => $project->id,
        'title' => 'Test conversation',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->ulid}/conversations/{$conversation->id}")
        ->assertForbidden();
});

test('conversation must belong to the project', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Project A']);
    $otherProject = Project::create(['name' => 'Project B']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $otherProject->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $conversation = Conversation::create([
        'id' => Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $otherProject->id,
        'title' => 'Other project conversation',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->ulid}/conversations/{$conversation->id}")
        ->assertNotFound();
});
