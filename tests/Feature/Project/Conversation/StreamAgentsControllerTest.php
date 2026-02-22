<?php

declare(strict_types=1);

use App\Ai\Agents\GenericAgent;
use App\Enums\AgentType;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);

    $this->agent = $this->project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $this->conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'user_id' => $this->user->id,
        'project_id' => $this->project->id,
        'title' => 'Test conversation',
    ]);

    ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'Original user question',
    ]);
});

// ============================================================================
// Authorization
// ============================================================================

test('guest cannot stream agents', function (): void {
    $this->postJson(route('projects.conversations.stream-agents', [
        $this->project,
        $this->conversation,
    ]), [
        'agent_ids' => [$this->agent->id],
    ])->assertUnauthorized();
});

test('non-member cannot stream agents', function (): void {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->postJson(route('projects.conversations.stream-agents', [
            $this->project,
            $this->conversation,
        ]), [
            'agent_ids' => [$this->agent->id],
        ])
        ->assertForbidden();
});

// ============================================================================
// Validation
// ============================================================================

test('stream-agents requires agent_ids', function (): void {
    $this->actingAs($this->user)
        ->postJson(route('projects.conversations.stream-agents', [
            $this->project,
            $this->conversation,
        ]), [])
        ->assertJsonValidationErrors('agent_ids');
});

test('stream-agents agent_ids must not be empty', function (): void {
    $this->actingAs($this->user)
        ->postJson(route('projects.conversations.stream-agents', [
            $this->project,
            $this->conversation,
        ]), [
            'agent_ids' => [],
        ])
        ->assertJsonValidationErrors('agent_ids');
});

// ============================================================================
// Streaming
// ============================================================================

test('stream-agents skips moderator and streams directly', function (): void {
    GenericAgent::fake(['Direct agent response']);
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->post(route('projects.conversations.stream-agents', [
            $this->project,
            $this->conversation,
        ]), [
            'agent_ids' => [$this->agent->id],
        ]);

    $content = streamResponse($response);

    expect($content)->toContain('"type":"routing"');
    expect($content)->toContain('"type":"agent_start"');
    expect($content)->toContain('"type":"agent_done"');
    expect($content)->toContain('"type":"done"');
    // No routing_poll — agents were explicitly chosen
    expect($content)->not->toContain('"type":"routing_poll"');
});

test('stream-agents saves response to database', function (): void {
    GenericAgent::fake(['Saved response']);
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->post(route('projects.conversations.stream-agents', [
            $this->project,
            $this->conversation,
        ]), [
            'agent_ids' => [$this->agent->id],
        ]);

    streamResponse($response);

    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversation->id)
            ->where('role', 'assistant')
            ->where('project_agent_id', $this->agent->id)
            ->count()
    )->toBe(1);
});

test('stream-agents returns SSE headers', function (): void {
    GenericAgent::fake(['Response']);
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->post(route('projects.conversations.stream-agents', [
            $this->project,
            $this->conversation,
        ]), [
            'agent_ids' => [$this->agent->id],
        ]);

    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');
});
