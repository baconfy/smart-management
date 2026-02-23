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

test('authenticated project member can rename a task', function () {
    $this->actingAs($this->user)
        ->patch(route('projects.tasks.rename', [$this->project, $this->task]), ['title' => 'New Title'])
        ->assertRedirect();

    expect($this->task->fresh()->title)->toBe('New Title');
});

test('guests cannot rename a task', function () {
    $this->patch(route('projects.tasks.rename', [$this->project, $this->task]), ['title' => 'New Title'])
        ->assertRedirect(route('login'));
});

test('non-members cannot rename a task', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->patch(route('projects.tasks.rename', [$this->project, $this->task]), ['title' => 'New Title'])
        ->assertForbidden();
});

test('title is required when renaming', function () {
    $this->actingAs($this->user)
        ->patch(route('projects.tasks.rename', [$this->project, $this->task]), ['title' => ''])
        ->assertSessionHasErrors('title');
});

test('title cannot exceed 255 characters', function () {
    $this->actingAs($this->user)
        ->patch(route('projects.tasks.rename', [$this->project, $this->task]), ['title' => str_repeat('a', 256)])
        ->assertSessionHasErrors('title');
});

test('cannot rename a task from another project', function () {
    $otherProject = Project::factory()->create();
    $otherProject->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    $otherTask = Task::factory()->create(['project_id' => $otherProject->id]);

    $this->actingAs($this->user)
        ->patch(route('projects.tasks.rename', [$this->project, $otherTask]), ['title' => 'New Title'])
        ->assertNotFound();
});
