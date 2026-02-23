<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
});

test('authenticated project member can delete a project', function () {
    $this->actingAs($this->user)
        ->delete(route('projects.settings.destroy', $this->project))
        ->assertRedirect(route('projects.index'));

    expect($this->project->fresh()->trashed())->toBeTrue();
});

test('guests cannot delete a project', function () {
    $this->delete(route('projects.settings.destroy', $this->project))
        ->assertRedirect(route('login'));
});

test('non-members cannot delete a project', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->delete(route('projects.settings.destroy', $this->project))
        ->assertForbidden();
});

test('deleting a project soft deletes it', function () {
    $this->actingAs($this->user)
        ->delete(route('projects.settings.destroy', $this->project));

    $this->assertSoftDeleted('projects', ['id' => $this->project->id]);
});
