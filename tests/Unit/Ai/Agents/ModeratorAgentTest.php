<?php

declare(strict_types=1);

use App\Ai\Agents\ModeratorAgent;
use App\Enums\AgentType;
use App\Models\Project;

beforeEach(function () {
    $this->project = Project::create(['name' => 'Test Project']);

    $this->architect = $this->project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    $this->analyst = $this->project->agents()->create([
        'type' => AgentType::Analyst->value,
        'name' => 'Analyst',
        'instructions' => 'You are an analyst.',
    ]);

    $this->pm = $this->project->agents()->create([
        'type' => AgentType::Pm->value,
        'name' => 'PM',
        'instructions' => 'You are a project manager.',
    ]);
});

test('it routes to a single agent with high confidence', function () {
    ModeratorAgent::fake([
        json_encode([
            'agents' => [
                ['type' => 'architect', 'confidence' => 0.95],
            ],
            'reasoning' => 'This is clearly an architecture question.',
        ]),
    ]);

    $moderator = new ModeratorAgent($this->project);
    $result = $moderator->route('Should I use PostgreSQL or MySQL?');

    expect($result)
        ->toHaveKey('agents')
        ->toHaveKey('reasoning')
        ->and($result['agents'])->toHaveCount(1)
        ->and($result['agents'][0]['type'])->toBe('architect')
        ->and($result['agents'][0]['confidence'])->toBe(0.95);
});

test('it can route to multiple agents', function () {
    ModeratorAgent::fake([
        json_encode([
            'agents' => [
                ['type' => 'architect', 'confidence' => 0.9],
                ['type' => 'pm', 'confidence' => 0.85],
            ],
            'reasoning' => 'Involves architecture and planning.',
        ]),
    ]);

    $moderator = new ModeratorAgent($this->project);
    $result = $moderator->route('Plan the database migration strategy.');

    expect($result['agents'])->toHaveCount(2);
});

test('it returns low confidence when uncertain', function () {
    ModeratorAgent::fake([
        json_encode([
            'agents' => [
                ['type' => 'analyst', 'confidence' => 0.5],
                ['type' => 'architect', 'confidence' => 0.4],
            ],
            'reasoning' => 'Could be either.',
        ]),
    ]);

    $moderator = new ModeratorAgent($this->project);
    $result = $moderator->route('Tell me more about that.');

    expect($result['agents'][0]['confidence'])->toBeLessThan(0.8);
});

test('it resolves project agents from routing result', function () {
    ModeratorAgent::fake([
        json_encode([
            'agents' => [
                ['type' => 'architect', 'confidence' => 0.9],
            ],
            'reasoning' => 'Architecture question.',
        ]),
    ]);

    $moderator = new ModeratorAgent($this->project);
    $result = $moderator->route('Should I use PostgreSQL?');

    $resolved = $moderator->resolveAgents($result);

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0]->id)->toBe($this->architect->id);
});

test('it filters agents below confidence threshold', function () {
    ModeratorAgent::fake([
        json_encode([
            'agents' => [
                ['type' => 'architect', 'confidence' => 0.9],
                ['type' => 'pm', 'confidence' => 0.3],
            ],
            'reasoning' => 'Mostly architecture.',
        ]),
    ]);

    $moderator = new ModeratorAgent($this->project);
    $result = $moderator->route('Should I use PostgreSQL?');

    $highConfidence = $moderator->highConfidenceAgents($result);

    expect($highConfidence)->toHaveCount(1)
        ->and($highConfidence[0]['type'])->toBe('architect');
});
