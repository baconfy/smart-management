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

test('creates a custom agent', function () {
    $this->actingAs($this->user)->post(route('projects.agents.store', $this->project), [
        'name' => 'DevOps',
        'instructions' => 'You are a DevOps engineer.',
    ])->assertRedirect();

    expect($this->project->agents()->where('name', 'DevOps')->first())
        ->not->toBeNull()
        ->type->toBe(\App\Enums\AgentType::Custom)
        ->is_default->toBeFalse()
        ->instructions->toBe('You are a DevOps engineer.');
});

test('requires name and instructions to create agent', function () {
    $response = $this->actingAs($this->user)->post(route('projects.agents.store', $this->project), []);

    $response->assertSessionHasErrors(['name', 'instructions']);
});
