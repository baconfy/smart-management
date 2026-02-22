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

test('resets a default agent instructions and name', function () {
    $agent = $this->project->agents()->where('type', AgentType::Architect->value)->first();
    $agent->update(['instructions' => 'Modified instructions', 'name' => 'Custom Name']);

    $original = file_get_contents(resource_path('instructions/architect.md'));

    $this->actingAs($this->user)
        ->post(route('projects.agents.reset', [$this->project, $agent]))
        ->assertRedirect();

    expect($agent->refresh())
        ->instructions->toBe($original)
        ->name->toBe('Architect');
});

test('cannot reset a custom agent', function () {
    $agent = $this->project->agents()->create([
        'type' => AgentType::Custom->value,
        'name' => 'DevOps',
        'instructions' => 'Custom agent.',
        'is_default' => false,
    ]);

    $this->actingAs($this->user)
        ->post(route('projects.agents.reset', [$this->project, $agent]))
        ->assertForbidden();
});

test('forbids non-members', function () {
    $stranger = User::factory()->create();
    $agent = $this->project->agents()->first();

    $this->actingAs($stranger)
        ->post(route('projects.agents.reset', [$this->project, $agent]))
        ->assertForbidden();
});
