<?php

declare(strict_types=1);

use App\Actions\Projects\SeedProjectAgents;
use App\Actions\Projects\SeedProjectStatuses;
use App\Jobs\ProcessAgentMessage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    (new SeedProjectStatuses)($this->project);
    (new SeedProjectAgents)($this->project);

    $this->status = $this->project->statuses()->where('is_default', true)->first();
    $this->task = $this->project->tasks()->create([
        'title' => 'Implement auth',
        'description' => 'JWT auth with refresh tokens',
        'task_status_id' => $this->status->id,
    ]);
});

test('creates conversation and redirects to task show', function () {
    Queue::fake();

    $this->actingAs($this->user)
        ->post(route('projects.tasks.start', [$this->project, $this->task]))
        ->assertRedirect();

    expect($this->task->refresh()->conversation)->not->toBeNull();

    Queue::assertPushed(ProcessAgentMessage::class);
});

test('does not create duplicate conversation', function () {
    Queue::fake();

    $this->actingAs($this->user)
        ->post(route('projects.tasks.start', [$this->project, $this->task]));

    $this->actingAs($this->user)
        ->post(route('projects.tasks.start', [$this->project, $this->task]))
        ->assertRedirect();

    expect($this->task->conversations ?? $this->task->conversation)->not->toBeNull();
    Queue::assertPushed(ProcessAgentMessage::class, 1);
});

test('requires authentication', function () {
    $this->post(route('projects.tasks.start', [$this->project, $this->task]))
        ->assertRedirect('/login');
});

test('forbids non-members', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('projects.tasks.start', [$this->project, $this->task]))
        ->assertForbidden();
});

test('creates a user message with task context', function () {
    Queue::fake();

    $this->actingAs($this->user)->post(route('projects.tasks.start', [$this->project, $this->task]));

    $conversation = $this->task->refresh()->conversation;
    $userMessage = $conversation->messages()->where('role', 'user')->first();

    expect($userMessage)
        ->not->toBeNull()
        ->user_id->toBe($this->user->id)
        ->content->toContain('Implement auth')
        ->content->toContain('JWT auth with refresh tokens');
});

test('user message is marked as hidden', function () {
    Queue::fake();

    $this->actingAs($this->user)
        ->post(route('projects.tasks.start', [$this->project, $this->task]));

    $conversation = $this->task->refresh()->conversation;
    $userMessage = $conversation->messages()->where('role', 'user')->first();

    expect($userMessage->meta)->toBe(['hidden' => true]);
});

test('marks task as in progress when starting conversation', function () {
    Queue::fake();

    expect($this->task->status->slug)->toBe('todo');

    $this->actingAs($this->user)->post(route('projects.tasks.start', [$this->project, $this->task]));

    $inProgress = $this->project->statuses()->where('slug', 'in_progress')->first();

    expect($this->task->refresh()->task_status_id)->toBe($inProgress->id);
});
