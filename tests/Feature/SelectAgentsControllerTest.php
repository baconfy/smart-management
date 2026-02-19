<?php

declare(strict_types=1);

use App\Enums\AgentType;
use App\Events\AgentsProcessing;
use App\Jobs\ProcessAgentMessage;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::create(['name' => 'Test']);
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);

    $this->conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'title' => 'Test',
    ]);

    $this->architect = $this->project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $this->analyst = $this->project->agents()->create([
        'type' => AgentType::Analyst->value,
        'name' => 'Analyst',
        'instructions' => 'You are an analyst.',
    ]);
});

test('it dispatches jobs for selected agents', function () {
    Queue::fake([ProcessAgentMessage::class]);
    Event::fake([AgentsProcessing::class]);

    $this->actingAs($this->user)->postJson(route('projects.conversations.select-agents', [$this->project, $this->conversation]), [
        'agent_ids' => [$this->architect->id, $this->analyst->id],
        'message' => 'Tell me more about the architecture.',
    ])->assertOk();

    Queue::assertPushed(ProcessAgentMessage::class, 2);
    Event::assertDispatched(AgentsProcessing::class, fn ($e) => count($e->agents) === 2);
});

test('it requires at least one agent', function () {
    $this->actingAs($this->user)->postJson(route('projects.conversations.select-agents', [$this->project, $this->conversation]), [
        'agent_ids' => [],
        'message' => 'Hello',
    ])->assertUnprocessable();
});

test('it rejects agents from other projects', function () {
    Queue::fake();

    $otherProject = Project::create(['name' => 'Other']);
    $otherAgent = $otherProject->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'Other.',
    ]);

    $this->actingAs($this->user)->postJson(route('projects.conversations.select-agents', [$this->project, $this->conversation]), [
        'agent_ids' => [$otherAgent->id],
        'message' => 'Hello',
    ])->assertUnprocessable();
});

test('unauthorized user cannot select agents', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->postJson(route('projects.conversations.select-agents', [$this->project, $this->conversation]), [
        'agent_ids' => [$this->architect->id],
        'message' => 'Hello',
    ])->assertForbidden();
});
