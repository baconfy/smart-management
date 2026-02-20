<?php

declare(strict_types=1);

use App\Ai\Agents\GenericAgent;
use App\Ai\Tools\CreateTask;
use App\Ai\Tools\ListTasks;
use App\Enums\AgentType;
use App\Models\Project;
use App\Models\ProjectAgent;

beforeEach(function () {
    $this->project = Project::factory()->create([
        'name' => 'Smart Management',
        'description' => 'AI-powered project management tool.',
    ]);

    $this->projectAgent = $this->project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a software architect.',
        'tools' => ['CreateTask', 'ListTasks'],
    ]);
});

// ============================================================================
// Instructions
// ============================================================================

test('instructions include agent instructions and project context', function (): void {
    $agent = new GenericAgent($this->projectAgent);

    $instructions = $agent->instructions();

    expect((string) $instructions)
        ->toContain('You are a software architect.')
        ->toContain('## Project Context')
        ->toContain('Name: Smart Management')
        ->toContain('Description: AI-powered project management tool.');
});

test('instructions exclude description when project has none', function (): void {
    $project = Project::create(['name' => 'No Description Project']);

    $projectAgent = $project->agents()->create([
        'type' => AgentType::Analyst->value,
        'name' => 'Analyst',
        'instructions' => 'You are an analyst.',
    ]);

    $agent = new GenericAgent($projectAgent);

    $instructions = $agent->instructions();

    expect((string) $instructions)
        ->toContain('Name: No Description Project')
        ->not->toContain('Description:');
});

// ============================================================================
// Tools
// ============================================================================

test('tools are loaded from project agent tools array', function (): void {
    $agent = new GenericAgent($this->projectAgent);

    $tools = $agent->tools();

    expect($tools)
        ->toBeArray()
        ->toHaveCount(2)
        ->and($tools[0])->toBeInstanceOf(CreateTask::class)
        ->and($tools[1])->toBeInstanceOf(ListTasks::class);
});

test('tools returns empty array when project agent has no tools', function (): void {
    $projectAgent = $this->project->agents()->create([
        'type' => AgentType::Analyst->value,
        'name' => 'Analyst',
        'instructions' => 'You are an analyst.',
        'tools' => null,
    ]);

    $agent = new GenericAgent($projectAgent);

    expect($agent->tools())->toBeArray()->toBeEmpty();
});

// ============================================================================
// Project
// ============================================================================

test('project returns the associated project', function (): void {
    $agent = new GenericAgent($this->projectAgent);

    expect($agent->project())
        ->toBeInstanceOf(Project::class)
        ->id->toBe($this->project->id);
});

test('project agent is accessible as public property', function (): void {
    $agent = new GenericAgent($this->projectAgent);

    expect($agent->projectAgent)
        ->toBeInstanceOf(ProjectAgent::class)
        ->id->toBe($this->projectAgent->id);
});
