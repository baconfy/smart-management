<?php

declare(strict_types=1);

use App\Actions\Projects\SeedProjectStatuses;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    (new SeedProjectStatuses)($this->project);
});

test('show returns task without conversation when not started', function (): void {
    $todo = $this->project->statuses()->where('slug', 'todo')->first();

    $task = $this->project->tasks()->create([
        'title' => 'Task',
        'description' => 'D',
        'task_status_id' => $todo->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('projects.tasks.show', [$this->project, $task]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/tasks/show')
            ->has('task')
            ->has('subtasks')
            ->has('implementationNotes')
            ->where('conversation', null)
            ->where('messages', [])
        );
});

test('show returns task with conversation and messages when started', function (): void {
    $todo = $this->project->statuses()->where('slug', 'todo')->first();

    $task = $this->project->tasks()->create([
        'title' => 'Task',
        'description' => 'D',
        'task_status_id' => $todo->id,
    ]);

    $conversation = Conversation::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'user_id' => $this->user->id,
        'project_id' => $this->project->id,
        'task_id' => $task->id,
        'title' => $task->title,
    ]);

    ConversationMessage::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'conversation_id' => $conversation->id,
        'user_id' => $this->user->id,
        'role' => 'assistant',
        'content' => 'Here are my observations about this task.',
    ]);

    $this->actingAs($this->user)
        ->get(route('projects.tasks.show', [$this->project, $task]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/tasks/show')
            ->has('task')
            ->has('conversation')
            ->has('messages.data', 1)
        );
});

test('show requires authentication', function (): void {
    $todo = $this->project->statuses()->where('slug', 'todo')->first();
    $task = $this->project->tasks()->create(['title' => 'T', 'description' => 'D', 'task_status_id' => $todo->id]);

    $this->get(route('projects.tasks.show', [$this->project, $task]))
        ->assertRedirect('/login');
});

test('show forbids non-members', function (): void {
    $stranger = User::factory()->create();
    $todo = $this->project->statuses()->where('slug', 'todo')->first();
    $task = $this->project->tasks()->create(['title' => 'T', 'description' => 'D', 'task_status_id' => $todo->id]);

    $this->actingAs($stranger)
        ->get(route('projects.tasks.show', [$this->project, $task]))
        ->assertForbidden();
});
