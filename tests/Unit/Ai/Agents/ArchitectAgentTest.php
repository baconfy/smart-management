<?php

declare(strict_types=1);

use App\Ai\Agents\ArchitectAgent;
use App\Enums\AgentType;
use App\Models\Project;
use App\Models\User;

// ============================================================================
// Agent Structure
// ============================================================================

test('architect agent loads instructions from project agent model', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $projectAgent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a senior software architect.',
    ]);

    $agent = ArchitectAgent::make(projectAgent: $projectAgent);

    expect((string) $agent->instructions())
        ->toContain('You are a senior software architect.')
        ->toContain('Test Project');
});

test('architect agent appends project context to instructions', function (): void {
    $project = Project::create(['name' => 'Arkham District', 'description' => 'Crypto payment gateway']);
    $projectAgent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a senior software architect.',
    ]);

    $agent = ArchitectAgent::make(projectAgent: $projectAgent);

    $instructions = (string) $agent->instructions();

    expect($instructions)
        ->toContain('You are a senior software architect.')
        ->toContain('Arkham District')
        ->toContain('Crypto payment gateway');
});

test('architect agent returns empty tools for now', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $projectAgent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a senior software architect.',
    ]);

    $agent = ArchitectAgent::make(projectAgent: $projectAgent);

    expect(iterator_to_array($agent->tools()))->toBeEmpty();
});

// ============================================================================
// Conversation Support
// ============================================================================

test('architect agent supports conversations', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $projectAgent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a senior software architect.',
    ]);
    $user = User::factory()->create();

    $agent = ArchitectAgent::make(projectAgent: $projectAgent);
    $agent->forUser($user);

    expect($agent->hasConversationParticipant())->toBeTrue();
    expect($agent->conversationParticipant()->id)->toBe($user->id);
});

test('architect agent can continue an existing conversation', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $projectAgent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a senior software architect.',
    ]);
    $user = User::factory()->create();

    $agent = ArchitectAgent::make(projectAgent: $projectAgent);
    $agent->continue('fake-conversation-id', $user);

    expect($agent->currentConversation())->toBe('fake-conversation-id');
});

// ============================================================================
// Prompt (Faked)
// ============================================================================

test('architect agent can be prompted with fake response', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $projectAgent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a senior software architect.',
    ]);

    ArchitectAgent::fake(['I recommend PostgreSQL for your database.']);

    $agent = ArchitectAgent::make(projectAgent: $projectAgent);
    $response = $agent->prompt('What database should I use?');

    expect($response->text)->toBe('I recommend PostgreSQL for your database.');
});

test('architect agent records that it was prompted', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $projectAgent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a senior software architect.',
    ]);

    ArchitectAgent::fake(['PostgreSQL is the best choice.']);

    $agent = ArchitectAgent::make(projectAgent: $projectAgent);
    $agent->prompt('What database should I use?');

    ArchitectAgent::assertPrompted('What database should I use?');
});

// ============================================================================
// Project Agent Accessor
// ============================================================================

test('architect agent exposes its project agent', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $projectAgent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are a senior software architect.',
    ]);

    $agent = ArchitectAgent::make(projectAgent: $projectAgent);

    expect($agent->projectAgent->id)->toBe($projectAgent->id);
    expect($agent->project()->id)->toBe($project->id);
});
