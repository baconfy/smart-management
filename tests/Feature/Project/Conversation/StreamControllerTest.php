<?php

declare(strict_types=1);

use App\Ai\Agents\GenericAgent;
use App\Ai\Agents\ModeratorAgent;
use App\Enums\AgentType;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// ============================================================================
// Authorization
// ============================================================================

test('guest cannot stream chat message', function (): void {
    $project = Project::factory()->create();

    $this->postJson(route('projects.conversations.stream', $project), [
        'message' => 'Hello',
    ])->assertUnauthorized();
});

test('non-member cannot stream chat message', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $other->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->postJson(route('projects.conversations.stream', $project), [
            'message' => 'Hello',
        ])
        ->assertForbidden();
});

// ============================================================================
// Validation
// ============================================================================

test('stream requires message', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->postJson(route('projects.conversations.stream', $project), [])
        ->assertJsonValidationErrors('message');
});

test('stream agent_ids must be an array', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->postJson(route('projects.conversations.stream', $project), [
            'message' => 'Hello',
            'agent_ids' => 'not-array',
        ])
        ->assertJsonValidationErrors('agent_ids');
});

test('stream agent_ids must belong to the project', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->postJson(route('projects.conversations.stream', $project), [
            'message' => 'Hello',
            'agent_ids' => [99999],
        ])
        ->assertJsonValidationErrors('agent_ids.0');
});

// ============================================================================
// SSE Response Headers
// ============================================================================

test('stream returns SSE content type headers', function (): void {
    GenericAgent::fake(['Streamed response']);
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $response = $this->actingAs($user)
        ->post(route('projects.conversations.stream', $project), [
            'message' => 'Hello',
            'agent_ids' => [$agent->id],
        ]);

    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');
    expect($response->headers->get('Cache-Control'))->toContain('no-cache');
    expect($response->headers->get('X-Accel-Buffering'))->toBe('no');
});

// ============================================================================
// Conversation Creation
// ============================================================================

test('stream creates conversation on first message', function (): void {
    GenericAgent::fake(['Response text']);
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $response = $this->actingAs($user)
        ->post(route('projects.conversations.stream', $project), [
            'message' => 'Design a system',
            'agent_ids' => [$agent->id],
        ]);

    // Stream the response to trigger the generator
    $content = streamResponse($response);

    $conversation = Conversation::where('project_id', $project->id)->first();

    expect($conversation)->not->toBeNull();
    expect($content)->toContain('"type":"conversation"');
    expect($content)->toContain('"isNew":true');
    expect($content)->toContain('"id":"'.$conversation->id.'"');
});

test('stream stores user message before streaming', function (): void {
    GenericAgent::fake(['Response text']);
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $response = $this->actingAs($user)
        ->post(route('projects.conversations.stream', $project), [
            'message' => 'What database?',
            'agent_ids' => [$agent->id],
        ]);

    streamResponse($response);

    $conversation = Conversation::where('project_id', $project->id)->first();

    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation->id)
            ->where('role', 'user')
            ->count()
    )->toBe(1);
});

test('stream continues existing conversation', function (): void {
    GenericAgent::fake(['Response text']);
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Existing conversation',
    ]);

    $response = $this->actingAs($user)
        ->post(route('projects.conversations.stream-continue', [$project, $conversation]), [
            'message' => 'Follow up question',
            'agent_ids' => [$agent->id],
        ]);

    $content = streamResponse($response);

    expect($content)->toContain('"isNew":false');
    expect(Conversation::where('project_id', $project->id)->count())->toBe(1);
});

// ============================================================================
// Agent Streaming
// ============================================================================

test('stream emits agent_start and agent_done events', function (): void {
    GenericAgent::fake(['Hello from architect']);
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $response = $this->actingAs($user)
        ->post(route('projects.conversations.stream', $project), [
            'message' => 'Hello',
            'agent_ids' => [$agent->id],
        ]);

    $content = streamResponse($response);

    expect($content)->toContain('"type":"agent_start"');
    expect($content)->toContain('"agentId":'.$agent->id);
    expect($content)->toContain('"type":"agent_done"');
    expect($content)->toContain('"type":"done"');
});

test('stream saves agent response to database after completion', function (): void {
    GenericAgent::fake(['The response content']);
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $response = $this->actingAs($user)
        ->post(route('projects.conversations.stream', $project), [
            'message' => 'Design it',
            'agent_ids' => [$agent->id],
        ]);

    streamResponse($response);

    $conversation = Conversation::where('project_id', $project->id)->first();

    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation->id)
            ->where('role', 'assistant')
            ->where('project_agent_id', $agent->id)
            ->count()
    )->toBe(1);
});

test('stream emits routing event with agent info', function (): void {
    GenericAgent::fake(['Response']);
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $response = $this->actingAs($user)
        ->post(route('projects.conversations.stream', $project), [
            'message' => 'Hello',
            'agent_ids' => [$agent->id],
        ]);

    $content = streamResponse($response);

    expect($content)->toContain('"type":"routing"');
    expect($content)->toContain('"name":"Architect"');
});

// ============================================================================
// Moderator Routing
// ============================================================================

test('stream routes via moderator when no agent_ids', function (): void {
    ModeratorAgent::fake([
        json_encode([
            'agents' => [['type' => 'architect', 'confidence' => 0.9]],
            'reasoning' => 'Architecture question.',
        ]),
    ]);
    GenericAgent::fake(['Moderator routed response']);
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $response = $this->actingAs($user)
        ->post(route('projects.conversations.stream', $project), [
            'message' => 'Design a microservice architecture',
        ]);

    $content = streamResponse($response);

    expect($content)->toContain('"type":"routing"');
    expect($content)->toContain('"type":"agent_start"');
    expect($content)->toContain('"type":"agent_done"');
    expect($content)->toContain('"type":"done"');
});

// ============================================================================
// Attachments
// ============================================================================

test('stream stores attachments with user message', function (): void {
    GenericAgent::fake(['Response']);
    Queue::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $response = $this->actingAs($user)
        ->post(route('projects.conversations.stream', $project), [
            'message' => 'Check this file',
            'agent_ids' => [$agent->id],
            'attachments' => [
                UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ],
        ]);

    streamResponse($response);

    $conversation = Conversation::where('project_id', $project->id)->first();
    $userMessage = DB::table('agent_conversation_messages')
        ->where('conversation_id', $conversation->id)
        ->where('role', 'user')
        ->first();

    $attachments = json_decode($userMessage->attachments, true);

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0]['filename'])->toBe('document.pdf')
        ->and($attachments[0]['mediaType'])->toBe('application/pdf');
});

test('stream rejects files exceeding max size', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->postJson(route('projects.conversations.stream', $project), [
            'message' => 'Hello',
            'attachments' => [
                UploadedFile::fake()->create('huge.pdf', 11000), // 11MB
            ],
        ])
        ->assertJsonValidationErrors('attachments.0');
});

test('stream rejects invalid file types', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->postJson(route('projects.conversations.stream', $project), [
            'message' => 'Hello',
            'attachments' => [
                UploadedFile::fake()->create('malware.exe', 100),
            ],
        ])
        ->assertJsonValidationErrors('attachments.0');
});

test('stream rejects more than 10 attachments', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $files = array_map(
        fn ($i) => UploadedFile::fake()->create("file{$i}.txt", 1, 'text/plain'),
        range(1, 11),
    );

    $this->actingAs($user)
        ->postJson(route('projects.conversations.stream', $project), [
            'message' => 'Hello',
            'attachments' => $files,
        ])
        ->assertJsonValidationErrors('attachments');
});

// ============================================================================
// Moderator Routing
// ============================================================================

test('stream sends routing_poll on low moderator confidence', function (): void {
    ModeratorAgent::fake([
        json_encode([
            'agents' => [
                ['type' => 'architect', 'confidence' => 0.6],
                ['type' => 'dba', 'confidence' => 0.5],
            ],
            'reasoning' => 'Ambiguous request.',
        ]),
    ]);
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'Test.',
    ]);
    $project->agents()->create([
        'type' => AgentType::Dba->value,
        'name' => 'DBA',
        'instructions' => 'Test.',
    ]);

    $response = $this->actingAs($user)
        ->post(route('projects.conversations.stream', $project), [
            'message' => 'What do you think?',
        ]);

    $content = streamResponse($response);

    expect($content)->toContain('"type":"routing_poll"');
    expect($content)->toContain('"reasoning":"Ambiguous request."');
    // No agent streaming should happen
    expect($content)->not->toContain('"type":"agent_start"');
    // Stream should close after poll
    expect($content)->toContain('"type":"done"');
});
