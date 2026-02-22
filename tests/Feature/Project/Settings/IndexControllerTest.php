<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
});

test('displays settings page', function () {
    $this->actingAs($this->user)->get(route('projects.settings', $this->project))->assertOk()->assertInertia(
        fn ($page) => $page->component('projects/settings/index')->has('project')
    );
});

test('requires authentication', function () {
    $this->get(route('projects.settings', $this->project))->assertRedirect('/login');
});

test('forbids non-members', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('projects.settings', $this->project))->assertForbidden();
});
