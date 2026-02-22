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

test('updates an agent', function () {
    $agent = $this->project->agents()->first();

    $this->actingAs($this->user)->put(route('projects.agents.update', [$this->project, $agent]), [
        'name' => 'Senior Architect',
        'instructions' => 'Updated instructions.',
        'model' => 'gpt-4o',
        'tools' => ['CreateDecision', 'ListDecisions'],
    ])->assertRedirect();

    expect($agent->refresh())
        ->name->toBe('Senior Architect')
        ->instructions->toBe('Updated instructions.')
        ->model->toBe('gpt-4o')
        ->tools->toBe(['CreateDecision', 'ListDecisions']);
});

test('requires name and instructions to update agent', function () {
    $agent = $this->project->agents()->first();

    $this->actingAs($this->user)->put(route('projects.agents.update', [$this->project, $agent]), [])
        ->assertSessionHasErrors(['name', 'instructions']);
});

test('returns 404 if agent belongs to another project', function () {
    $otherProject = Project::factory()->create();
    (new SeedProjectAgents)($otherProject);
    $otherAgent = $otherProject->agents()->first();

    $this->actingAs($this->user)->put(route('projects.agents.update', [$this->project, $otherAgent]), [
        'name' => 'Hacked',
        'instructions' => 'Hacked',
    ])->assertNotFound();
});
