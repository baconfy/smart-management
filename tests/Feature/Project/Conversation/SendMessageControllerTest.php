<?php

declare(strict_types=1);

use App\Ai\Agents\ModeratorAgent;
use App\Enums\AgentType;
use App\Jobs\GenerateConversationTitle;
use App\Jobs\ProcessChatMessage;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ============================================================================
// Authorization
// ============================================================================

test('guest cannot send chat message', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $this->postJson(route('projects.conversations.send', $project), ['message' => 'Hello', 'agent_ids' => [1]])->assertUnauthorized();
});

test('non-member cannot send chat message', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $other->id, 'role' => 'owner']);
    $agent = $project->agents()->create(['type' => AgentType::Architect->value, 'name' => 'Architect', 'instructions' => 'Test.']);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'Hello', 'agent_ids' => [$agent->id]])->assertForbidden();
});

// ============================================================================
// Validation
// ============================================================================

test('message is required', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['agent_ids' => [1]])->assertJsonValidationErrors('message');
});

test('agent_ids must be an array', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'Hello', 'agent_ids' => 'not-array'])->assertJsonValidationErrors('agent_ids');
});

test('agent_ids must belong to the project', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'Hello', 'agent_ids' => [99999]])->assertJsonValidationErrors('agent_ids.0');
});

test('agent_ids can be empty for moderator routing', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    ModeratorAgent::fake([json_encode(['agents' => [['type' => 'architect', 'confidence' => 0.5]], 'reasoning' => 'Generic.'])]);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'Hello', 'agent_ids' => []])->assertOk()->assertJsonStructure(['conversation_id']);
});

// ============================================================================
// Single Agent Flow
// ============================================================================

test('member can send a message and conversation is created', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create(['type' => AgentType::Architect->value, 'name' => 'Architect', 'instructions' => 'You are an architect.']);

    $response = $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'What database should I use?', 'agent_ids' => [$agent->id]])->assertOk()->assertJsonStructure(['conversation_id']);

    $conversation = Conversation::where('project_id', $project->id)->first();

    expect($conversation)->not->toBeNull();
    expect($response->json('conversation_id'))->toBe($conversation->id);
});

test('user message is stored and agent job is dispatched', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create(['type' => AgentType::Architect->value, 'name' => 'Architect', 'instructions' => 'You are an architect.']);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'What database?', 'agent_ids' => [$agent->id]])->assertOk()->assertJsonStructure(['conversation_id']);

    $conversation = Conversation::where('project_id', $project->id)->first();

    // User message stored immediately
    expect(DB::table('agent_conversation_messages')->where('conversation_id', $conversation->id)->where('role', 'user')->count())->toBe(1);

    // No assistant message yet (it's async)
    expect(DB::table('agent_conversation_messages')->where('conversation_id', $conversation->id)->where('role', 'assistant')->count())->toBe(0);

    // Job dispatched
    Queue::assertPushed(ProcessChatMessage::class);
});

test('new conversation dispatches title generation job', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    ModeratorAgent::fake([json_encode(['agents' => [['type' => 'architect', 'confidence' => 0.5]], 'reasoning' => 'Generic.'])]);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'Hello world', 'agent_ids' => []])->assertOk()->assertJsonStructure(['conversation_id']);

    Queue::assertPushed(GenerateConversationTitle::class);
});

test('continuing conversation does not dispatch title generation', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create(['type' => AgentType::Architect->value, 'name' => 'Architect', 'instructions' => 'You are an architect.']);

    // The first message creates conversation
    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'First question', 'agent_ids' => [$agent->id]]);

    $conversation = Conversation::where('project_id', $project->id)->first();

    Queue::fake(); // Reset queue

    // The second message continues
    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'Follow up', 'agent_ids' => [$agent->id], 'conversation_id' => $conversation->id])->assertOk()->assertJsonStructure(['conversation_id']);

    Queue::assertPushed(ProcessChatMessage::class);
    Queue::assertNotPushed(GenerateConversationTitle::class);
});

// ============================================================================
// Multi-Agent Flow
// ============================================================================

test('one job is dispatched per selected agent', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $architect = $project->agents()->create(['type' => AgentType::Architect->value, 'name' => 'Architect', 'instructions' => 'You are an architect.']);
    $analyst = $project->agents()->create(['type' => AgentType::Analyst->value, 'name' => 'Analyst', 'instructions' => 'You are an analyst.']);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'What do you think?', 'agent_ids' => [$architect->id, $analyst->id]])->assertOk()->assertJsonStructure(['conversation_id']);

    Queue::assertPushed(ProcessChatMessage::class, function ($job) use ($architect, $analyst) {
        return $job->agentIds === [$architect->id, $analyst->id];
    });

    // Only 1 user message (no duplicates)
    $conversation = Conversation::where('project_id', $project->id)->first();
    expect(DB::table('agent_conversation_messages')->where('conversation_id', $conversation->id)->where('role', 'user')->count())->toBe(1);
});

// ============================================================================
// Moderator Routing
// ============================================================================

test('empty agent_ids triggers moderator routing', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'Should I use PostgreSQL?', 'agent_ids' => []])->assertOk()->assertJsonStructure(['conversation_id']);

    Queue::assertPushed(ProcessChatMessage::class, function ($job) {
        return empty($job->agentIds);
    });
});

test('moderator routes to multiple agents', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'Analyze the database architecture.', 'agent_ids' => []])->assertOk()->assertJsonStructure(['conversation_id']);

    Queue::assertPushed(ProcessChatMessage::class, function ($job) {
        return empty($job->agentIds);
    });
});

test('moderator broadcasts agents processing event', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->postJson(route('projects.conversations.send', $project), ['message' => 'Should I use PostgreSQL?', 'agent_ids' => []])->assertOk()->assertJsonStructure(['conversation_id']);

    Queue::assertPushed(ProcessChatMessage::class);
});
