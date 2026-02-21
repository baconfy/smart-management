<?php

declare(strict_types=1);

use App\Actions\Projects\SeedProjectAgents;
use App\Actions\Projects\SeedProjectStatuses;
use App\Jobs\ProcessChatMessage;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
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

test('stores user message and dispatches processing job', function () {
    Queue::fake();

    $this->actingAs($this->user)
        ->postJson(route('projects.tasks.send', [$this->project, $this->task]), [
            'message' => 'How should I handle token refresh?',
        ])
        ->assertOk()
        ->assertJsonStructure(['conversation_id']);

    expect($this->conversation->messages()->where('role', 'user')->count())->toBe(1);
    expect($this->conversation->messages()->first()->content)->toBe('How should I handle token refresh?');

    Queue::assertPushed(ProcessChatMessage::class);
});

test('sends message with specific agent ids', function () {
    Queue::fake();

    $technical = $this->project->agents()->where('type', 'technical')->first();
    $architect = $this->project->agents()->where('type', 'architect')->first();

    $this->actingAs($this->user)
        ->postJson(route('projects.tasks.send', [$this->project, $this->task]), [
            'message' => 'Review this approach',
            'agent_ids' => [$technical->id, $architect->id],
        ])
        ->assertOk()
        ->assertJsonStructure(['conversation_id']);

    Queue::assertPushed(ProcessChatMessage::class, function ($job) use ($technical, $architect) {
        return in_array($technical->id, $job->agentIds ?? [])
            || in_array($architect->id, $job->agentIds ?? []);
    });
});

test('returns 404 if task has no conversation', function () {
    $taskWithoutConvo = $this->project->tasks()->create([
        'title' => 'No chat',
        'description' => 'D',
        'task_status_id' => $this->status->id,
    ]);

    $this->actingAs($this->user)
        ->postJson(route('projects.tasks.send', [$this->project, $taskWithoutConvo]), [
            'message' => 'Hello',
        ])
        ->assertNotFound();
});

test('requires authentication', function () {
    $this->postJson(route('projects.tasks.send', [$this->project, $this->task]), [
        'message' => 'Hello',
    ])->assertUnauthorized();
});

test('forbids non-members', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->postJson(route('projects.tasks.send', [$this->project, $this->task]), [
            'message' => 'Hello',
        ])
        ->assertForbidden();
});
