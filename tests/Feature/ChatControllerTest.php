<?php

declare(strict_types=1);

use App\Ai\Agents\ArchitectAgent;
use App\Ai\Agents\GenericAgent;
use App\Enums\AgentType;
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

    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'agent_ids' => [1],
        ])->assertJsonValidationErrors('message');
});

test('agent_ids is required', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'Hello',
        ])->assertJsonValidationErrors('agent_ids');
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

test('conversation_id is optional', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    ArchitectAgent::fake(['Sure thing.']);

    $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'Hello',
            'agent_ids' => [$agent->id],
        ])->assertOk();
});

// ============================================================================
// Single Agent Flow
// ============================================================================

test('member can send a message and receives conversation_id', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    ArchitectAgent::fake(['I recommend PostgreSQL.']);

    $response = $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'What database should I use?',
            'agent_ids' => [$agent->id],
        ]);

    $response->assertOk()
        ->assertJsonStructure(['conversation_id']);

    expect($response->json('conversation_id'))->not->toBeNull();
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

    $response = $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'What database?',
            'agent_ids' => [$agent->id],
        ]);

    $conversationId = $response->json('conversation_id');

    $this->assertDatabaseHas('agent_conversations', [
        'id' => $conversationId,
        'user_id' => $user->id,
        'project_id' => $project->id,
    ]);

    // Exactly 1 user message
    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('role', 'user')
            ->count()
    )->toBe(1);

    // Exactly 1 assistant message
    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
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
    $first = $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'First question',
            'agent_ids' => [$agent->id],
        ]);

    $conversationId = $first->json('conversation_id');

    // Second message — continues
    $second = $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'Follow up',
            'agent_ids' => [$agent->id],
            'conversation_id' => $conversationId,
        ]);

    expect($second->json('conversation_id'))->toBe($conversationId);

    // 2 user + 2 assistant = 4 messages
    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
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

    $response = $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'What do you think?',
            'agent_ids' => [$architect->id, $analyst->id],
        ]);

    $response->assertOk();
    $conversationId = $response->json('conversation_id');

    // Only 1 user message (no duplicates!)
    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
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

    $response = $this->actingAs($user)
        ->postJson("/projects/{$project->ulid}/chat", [
            'message' => 'Analyze this',
            'agent_ids' => [$architect->id, $analyst->id],
        ]);

    $conversationId = $response->json('conversation_id');

    // 1 user + N assistant messages
    $assistantMessages = DB::table('agent_conversation_messages')
        ->where('conversation_id', $conversationId)
        ->where('role', 'assistant')
        ->pluck('project_agent_id')
        ->toArray();

    // At minimum, architect responded
    expect($assistantMessages)->toContain($architect->id);
    expect($assistantMessages)->toContain($analyst->id);
    expect($assistantMessages)->toHaveCount(2);
});
