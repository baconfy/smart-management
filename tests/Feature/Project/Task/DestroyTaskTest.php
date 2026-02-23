<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    $this->task = Task::factory()->create(['project_id' => $this->project->id]);
});

test('authenticated project member can delete a task', function () {
    $this->actingAs($this->user)
        ->delete(route('projects.tasks.destroy', [$this->project, $this->task]))
        ->assertRedirect(route('projects.tasks.index', $this->project));

    expect($this->task->fresh()->trashed())->toBeTrue();
});

test('guests cannot delete a task', function () {
    $this->delete(route('projects.tasks.destroy', [$this->project, $this->task]))
        ->assertRedirect(route('login'));
});

test('non-members cannot delete a task', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->delete(route('projects.tasks.destroy', [$this->project, $this->task]))
        ->assertForbidden();
});

test('cannot delete a task from another project', function () {
    $otherProject = Project::factory()->create();
    $otherProject->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    $otherTask = Task::factory()->create(['project_id' => $otherProject->id]);

    $this->actingAs($this->user)
        ->delete(route('projects.tasks.destroy', [$this->project, $otherTask]))
        ->assertNotFound();
});

test('deleting a task soft deletes it', function () {
    $this->actingAs($this->user)
        ->delete(route('projects.tasks.destroy', [$this->project, $this->task]));

    $this->assertSoftDeleted('tasks', ['id' => $this->task->id]);
});
