<?php

declare(strict_types=1);

use App\Actions\Projects\SeedProjectAgents;
use App\Jobs\ProcessAgentMessage;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\StartTaskConversation;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    app(SeedProjectAgents::class)($this->project);
    $this->status = $this->project->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0, 'is_default' => true]);
    $this->task = Task::factory()->create([
        'project_id' => $this->project->id,
        'task_status_id' => $this->status->id,
        'title' => 'Implement user authentication',
        'description' => 'Add JWT-based auth with refresh tokens',
    ]);
});

test('creates a conversation linked to the task', function () {
    Queue::fake();

    $action = app(StartTaskConversation::class);
    $conversation = $action($this->task, $this->user);

    expect($conversation)
        ->toBeInstanceOf(Conversation::class)
        ->task_id->toBe($this->task->id)
        ->project_id->toBe($this->project->id)
        ->user_id->toBe($this->user->id)
        ->title->toBe($this->task->title);
});

test('dispatches agent message job for technical agent', function () {
    Queue::fake();

    $action = app(StartTaskConversation::class);
    $action($this->task, $this->user);

    Queue::assertPushed(ProcessAgentMessage::class, function ($job) {
        return $job->projectAgent->type->value === 'technical'
            && str_contains($job->message, $this->task->title);
    });
});

test('returns existing conversation if task already has one', function () {
    Queue::fake();

    $action = app(StartTaskConversation::class);
    $first = $action($this->task, $this->user);
    $second = $action($this->task, $this->user);

    expect($second->id)->toBe($first->id);
    expect(Conversation::where('task_id', $this->task->id)->count())->toBe(1);

    Queue::assertPushed(ProcessAgentMessage::class, 1);
});

test('includes task context in the agent message', function () {
    Queue::fake();

    $action = app(StartTaskConversation::class);
    $action($this->task, $this->user);

    Queue::assertPushed(ProcessAgentMessage::class, function ($job) {
        return str_contains($job->message, 'Implement user authentication')
            && str_contains($job->message, 'JWT-based auth with refresh tokens');
    });
});
