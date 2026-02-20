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

// ============================================================================
// Index (Kanban Board)
// ============================================================================

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

// ============================================================================
// Update (Kanban Drag & Drop)
// ============================================================================

test('update changes task status', function (): void {
    $todo = $this->project->statuses()->where('slug', 'todo')->first();
    $done = $this->project->statuses()->where('slug', 'done')->first();

    $task = $this->project->tasks()->create([
        'title' => 'Task',
        'description' => 'D',
        'task_status_id' => $todo->id,
    ]);

    $this->actingAs($this->user)
        ->patch(route('projects.tasks.update', [$this->project, $task]), [
            'task_status_id' => $done->id,
        ])
        ->assertRedirect();

    expect($task->refresh()->task_status_id)->toBe($done->id);
});

test('update changes sort order', function (): void {
    $todo = $this->project->statuses()->where('slug', 'todo')->first();

    $task = $this->project->tasks()->create([
        'title' => 'Task',
        'description' => 'D',
        'task_status_id' => $todo->id,
        'sort_order' => 0,
    ]);

    $this->actingAs($this->user)
        ->patch(route('projects.tasks.update', [$this->project, $task]), [
            'sort_order' => 3,
        ])
        ->assertRedirect();

    expect($task->refresh()->sort_order)->toBe(3);
});

test('update changes status and sort order together', function (): void {
    $todo = $this->project->statuses()->where('slug', 'todo')->first();
    $inProgress = $this->project->statuses()->where('slug', 'in_progress')->first();

    $task = $this->project->tasks()->create([
        'title' => 'Task',
        'description' => 'D',
        'task_status_id' => $todo->id,
        'sort_order' => 0,
    ]);

    $this->actingAs($this->user)
        ->patch(route('projects.tasks.update', [$this->project, $task]), [
            'task_status_id' => $inProgress->id,
            'sort_order' => 2,
        ])
        ->assertRedirect();

    $task->refresh();
    expect($task->task_status_id)->toBe($inProgress->id);
    expect($task->sort_order)->toBe(2);
});

test('update rejects invalid status id', function (): void {
    $todo = $this->project->statuses()->where('slug', 'todo')->first();

    $task = $this->project->tasks()->create([
        'title' => 'Task',
        'description' => 'D',
        'task_status_id' => $todo->id,
    ]);

    $this->actingAs($this->user)
        ->patch(route('projects.tasks.update', [$this->project, $task]), [
            'task_status_id' => 99999,
        ])
        ->assertSessionHasErrors('task_status_id');
});

test('update forbids non-members', function (): void {
    $stranger = User::factory()->create();
    $todo = $this->project->statuses()->where('slug', 'todo')->first();

    $task = $this->project->tasks()->create([
        'title' => 'Task',
        'description' => 'D',
        'task_status_id' => $todo->id,
    ]);

    $this->actingAs($stranger)->patch(route('projects.tasks.update', [$this->project, $task]), [
        'task_status_id' => $todo->id,
    ])->assertForbidden();
});

test('update rejects task from another project', function (): void {
    $otherProject = Project::factory()->create();
    $otherProject->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    (new SeedProjectStatuses)($otherProject);

    $otherStatus = $otherProject->statuses()->where('slug', 'todo')->first();
    $otherTask = $otherProject->tasks()->create([
        'title' => 'Other Task',
        'description' => 'D',
        'task_status_id' => $otherStatus->id,
    ]);

    $this->actingAs($this->user)
        ->patch(route('projects.tasks.update', [$this->project, $otherTask]), [
            'task_status_id' => $otherStatus->id,
        ])
        ->assertNotFound();
});
