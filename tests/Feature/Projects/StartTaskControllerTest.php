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
