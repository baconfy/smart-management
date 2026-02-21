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
