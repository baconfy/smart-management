<?php

declare(strict_types=1);

use App\Actions\Projects\SeedProjectAgents;
use App\Actions\Projects\SeedProjectStatuses;
use App\Ai\Agents\GenericAgent;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    (new SeedProjectStatuses)($this->project);
    (new SeedProjectAgents)($this->project);

    $this->status = $this->project->statuses()->where('is_default', true)->first();
    $this->task = $this->project->tasks()->create([
        'title' => 'Implement auth',
        'description' => 'JWT auth',
        'task_status_id' => $this->status->id,
    ]);
    $this->conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'user_id' => $this->user->id,
        'project_id' => $this->project->id,
        'task_id' => $this->task->id,
        'title' => $this->task->title,
    ]);
});

// ============================================================================
// Authorization
// ============================================================================

test('guest cannot stream task messages', function (): void {
    $this->postJson(route('projects.tasks.stream', [
        $this->project,
        $this->task,
    ]), [
        'message' => 'Hello',
    ])->assertUnauthorized();
});

test('non-member cannot stream task messages', function (): void {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->postJson(route('projects.tasks.stream', [
            $this->project,
            $this->task,
        ]), [
            'message' => 'Hello',
        ])
        ->assertForbidden();
});

// ============================================================================
// Task Streaming
// ============================================================================

test('task stream pre-selects Technical agent', function (): void {
    GenericAgent::fake(['Technical analysis']);
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->post(route('projects.tasks.stream', [
            $this->project,
            $this->task,
        ]), [
            'message' => 'How should I handle token refresh?',
        ]);

    $content = streamResponse($response);

    $technicalAgent = $this->project->agents()->where('type', 'technical')->first();

    expect($content)->toContain('"type":"conversation"');
    expect($content)->toContain('"type":"routing"');
    expect($content)->toContain('"name":"Technical"');
    expect($content)->toContain('"type":"agent_start"');
    expect($content)->toContain('"agentId":'.$technicalAgent->id);
    expect($content)->toContain('"type":"agent_done"');
    expect($content)->toContain('"type":"done"');
});

test('task stream stores user message', function (): void {
    GenericAgent::fake(['Response']);
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->post(route('projects.tasks.stream', [
            $this->project,
            $this->task,
        ]), [
            'message' => 'Implement this feature',
        ]);

    streamResponse($response);

    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversation->id)
            ->where('role', 'user')
            ->where('content', 'Implement this feature')
            ->count()
    )->toBe(1);
});

test('task stream saves agent response', function (): void {
    GenericAgent::fake(['Agent response content']);
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->post(route('projects.tasks.stream', [
            $this->project,
            $this->task,
        ]), [
            'message' => 'Analyze this',
        ]);

    streamResponse($response);

    expect(
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversation->id)
            ->where('role', 'assistant')
            ->count()
    )->toBe(1);
});

test('task stream returns 404 if no conversation', function (): void {
    $taskWithoutConvo = $this->project->tasks()->create([
        'title' => 'No chat',
        'description' => 'D',
        'task_status_id' => $this->status->id,
    ]);

    $this->actingAs($this->user)
        ->postJson(route('projects.tasks.stream', [
            $this->project,
            $taskWithoutConvo,
        ]), [
            'message' => 'Hello',
        ])
        ->assertNotFound();
});

test('task stream returns SSE headers', function (): void {
    GenericAgent::fake(['Response']);
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->post(route('projects.tasks.stream', [
            $this->project,
            $this->task,
        ]), [
            'message' => 'Hello',
        ]);

    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');
});
