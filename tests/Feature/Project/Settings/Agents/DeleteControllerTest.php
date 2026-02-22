<?php

declare(strict_types=1);

use App\Actions\Projects\SeedProjectAgents;
use App\Enums\AgentType;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    (new SeedProjectAgents)($this->project);
});

test('deletes a custom agent', function () {
    $agent = $this->project->agents()->create(['name' => 'DevOps', 'instructions' => 'DevOps agent.', 'type' => AgentType::Custom->value]);

    $this->actingAs($this->user)
        ->delete(route('projects.agents.destroy', [$this->project, $agent]))
        ->assertRedirectToRoute('projects.agents.index', $this->project);

    $this->assertSoftDeleted($agent->refresh());
});

test('returns 404 if deleting agent from another project', function () {
    $otherProject = Project::factory()->create();
    (new SeedProjectAgents)($otherProject);
    $otherAgent = $otherProject->agents()->first();

    $this->actingAs($this->user)
        ->delete(route('projects.agents.destroy', [$this->project, $otherAgent]))
        ->assertNotFound();
});
