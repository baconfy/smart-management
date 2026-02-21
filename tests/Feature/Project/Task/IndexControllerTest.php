<?php

declare(strict_types=1);

use App\Actions\Projects\SeedProjectStatuses;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    (new SeedProjectStatuses)($this->project);
});

test('index returns tasks with statuses for kanban', function (): void {
    $todo = $this->project->statuses()->where('slug', 'todo')->first();

    $this->project->tasks()->create([
        'title' => 'Task A',
        'description' => 'D',
        'task_status_id' => $todo->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('projects.tasks.index', $this->project));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/tasks/index')
        ->has('statuses', 3)
        ->has('tasks', 1)
    );
});

test('index excludes subtasks from root level', function (): void {
    $todo = $this->project->statuses()->where('slug', 'todo')->first();

    $parent = $this->project->tasks()->create([
        'title' => 'Parent',
        'description' => 'D',
        'task_status_id' => $todo->id,
    ]);

    $this->project->tasks()->create([
        'title' => 'Subtask',
        'description' => 'D',
        'task_status_id' => $todo->id,
        'parent_task_id' => $parent->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('projects.tasks.index', $this->project))
        ->assertInertia(fn ($page) => $page->has('tasks', 1));
});

test('index requires authentication', function (): void {
    $this->get(route('projects.tasks.index', $this->project))
        ->assertRedirect('/login');
});

test('index forbids non-members', function (): void {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('projects.tasks.index', $this->project))
        ->assertForbidden();
});
