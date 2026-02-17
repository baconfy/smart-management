<?php

declare(strict_types=1);

use App\Ai\Agents\ArchitectAgent;
use App\Ai\Agents\GenericAgent;
use App\Enums\AgentType;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ============================================================================
// Authorization
// ============================================================================

test('guest cannot send chat message', function (): void {
    $project = Project::create(['name' => 'Test']);

    $this->postJson("/projects/{$project->ulid}/chat", [
        'message' => 'Hello',
        'agent_ids' => [1],
    ])->assertUnauthorized();
});

test('non-member cannot send chat message', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $other->id, 'role' => 'owner']);
    $agent = $project->agents()->create(['type' => AgentType::Architect->value, 'name' => 'Architect', 'instructions' => 'Test.']);

    $this->actingAs($user)->postJson("/projects/{$project->ulid}/chat", ['message' => 'Hello', 'agent_ids' => [$agent->id]])->assertForbidden();
});

// ============================================================================
// Validation
// ============================================================================

test('message is required', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->postJson("/projects/{$project->ulid}/chat", ['agent_ids' => [1]])->assertJsonValidationErrors('message');
});

test('agent_ids must be an array', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'Hello',
            'agent_ids' => 'not-array',
        ])->assertJsonValidationErrors('agent_ids');
});

test('agent_ids must belong to the project', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'Hello',
            'agent_ids' => [99999],
        ])->assertJsonValidationErrors('agent_ids.0');
});

test('agent_ids can be empty for moderator routing', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'Hello',
            'agent_ids' => [],
        ])->assertRedirect();
});

// ============================================================================
// Single Agent Flow
// ============================================================================

test('member can send a message and conversation is created', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    ArchitectAgent::fake(['I recommend PostgreSQL.']);

    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'What database should I use?',
            'agent_ids' => [$agent->id],
        ])->assertRedirect();

    $conversation = Conversation::where('project_id', $project->id)->first();

    expect($conversation)->not->toBeNull();
});

test('conversation is persisted with one user message', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    ArchitectAgent::fake(['PostgreSQL is great.']);

    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'What database?',
            'agent_ids' => [$agent->id],
        ])->assertRedirect();

    $conversation = Conversation::where('project_id', $project->id)->first();

    $this->assertDatabaseHas('agent_conversations', [
        'id' => $conversation->id,
        'user_id' => $user->id,
        'project_id' => $project->id,
    ]);

    // Exactly 1 user message
    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation->id)
            ->where('role', 'user')
            ->count()
    )->toBe(1);

    // Exactly 1 assistant message
    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation->id)
            ->where('role', 'assistant')
            ->where('project_agent_id', $agent->id)
            ->count()
    )->toBe(1);
});

test('can continue an existing conversation', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    ArchitectAgent::fake(['First.', 'Second.']);

    // First message
    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'First question',
            'agent_ids' => [$agent->id],
        ])->assertRedirect();

    $conversation = Conversation::where('project_id', $project->id)->first();

    // Second message — continues
    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'Follow up',
            'agent_ids' => [$agent->id],
            'conversation_id' => $conversation->id,
        ])->assertRedirect();

    // Still only 1 conversation
    expect(Conversation::where('project_id', $project->id)->count())->toBe(1);

    // 2 user + 2 assistant = 4 messages
    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation->id)
            ->count()
    )->toBe(4);
});

// ============================================================================
// Multi-Agent Flow
// ============================================================================

test('message is sent to multiple agents', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $architect = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $analyst = $project->agents()->create([
        'type' => AgentType::Analyst->value,
        'name' => 'Analyst',
        'instructions' => 'You are an analyst.',
    ]);

    ArchitectAgent::fake(['Architect says hi.']);
    GenericAgent::fake(['Analyst says hi.']);

    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'What do you think?',
            'agent_ids' => [$architect->id, $analyst->id],
        ])->assertRedirect();

    $conversation = Conversation::where('project_id', $project->id)->first();

    // Only 1 user message (no duplicates!)
    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation->id)
            ->where('role', 'user')
            ->count()
    )->toBe(1);
});

test('each agent stores its own response', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $architect = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $analyst = $project->agents()->create([
        'type' => AgentType::Analyst->value,
        'name' => 'Analyst',
        'instructions' => 'You are an analyst.',
    ]);

    ArchitectAgent::fake(['Architect response.']);
    GenericAgent::fake(['Analyst response.']);

    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'Analyze this',
            'agent_ids' => [$architect->id, $analyst->id],
        ])->assertRedirect();

    $conversation = Conversation::where('project_id', $project->id)->first();

    // 1 user + N assistant messages
    $assistantMessages = DB::table('agent_conversation_messages')
        ->where('conversation_id', $conversation->id)
        ->where('role', 'assistant')
        ->pluck('project_agent_id')
        ->toArray();

    // At minimum, the architect responded
    expect($assistantMessages)->toContain($architect->id);
    expect($assistantMessages)->toContain($analyst->id);
    expect($assistantMessages)->toHaveCount(2);
});
