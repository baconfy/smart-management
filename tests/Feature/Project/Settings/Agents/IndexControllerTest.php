<?php

declare(strict_types=1);

use App\Actions\Projects\SeedProjectAgents;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    (new SeedProjectAgents)($this->project);
});

test('lists project agents', function () {
    $this->actingAs($this->user)
        ->get(route('projects.agents.index', $this->project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/settings/agents/index')
            ->has('project')
            ->has('agents', 5)
        );
});

test('requires authentication', function () {
    $this->get(route('projects.agents.index', $this->project))
        ->assertRedirect('/login');
});

test('forbids non-members', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('projects.agents.index', $this->project))
        ->assertForbidden();
});
