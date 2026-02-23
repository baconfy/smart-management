<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
});

test('authenticated project member can update project name', function () {
    $this->actingAs($this->user)
        ->patch(route('projects.settings.update', $this->project), [
            'name' => 'Updated Project Name',
        ])
        ->assertRedirect();

    expect($this->project->fresh()->name)->toBe('Updated Project Name');
});

test('requires a name', function () {
    $this->actingAs($this->user)
        ->patch(route('projects.settings.update', $this->project), [
            'name' => '',
        ])
        ->assertSessionHasErrors('name');
});

test('name cannot exceed 255 characters', function () {
    $this->actingAs($this->user)
        ->patch(route('projects.settings.update', $this->project), [
            'name' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors('name');
});

test('guests cannot update a project', function () {
    $this->patch(route('projects.settings.update', $this->project), [
        'name' => 'Updated',
    ])->assertRedirect(route('login'));
});

test('non-members cannot update a project', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->patch(route('projects.settings.update', $this->project), [
            'name' => 'Updated',
        ])
        ->assertForbidden();
});
