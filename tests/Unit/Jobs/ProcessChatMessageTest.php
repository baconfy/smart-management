<?php

declare(strict_types=1);

use App\Ai\Agents\ModeratorAgent;
use App\Enums\AgentType;
use App\Events\AgentSelectionRequired;
use App\Events\AgentsProcessing;
use App\Events\RoutingFailed;
use App\Jobs\ProcessAgentMessage;
use App\Jobs\ProcessChatMessage;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->project = Project::factory()->create(['name' => 'Test']);
    $this->user = User::factory()->create();

    $this->conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'title' => 'Test',
    ]);

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
});

test('it routes via moderator when no agent ids provided', function () {
    Queue::fake([ProcessAgentMessage::class]);
    Event::fake([AgentsProcessing::class]);

    ModeratorAgent::fake([
        json_encode([
            'agents' => [['type' => 'architect', 'confidence' => 0.95]],
            'reasoning' => 'Architecture question.',
        ]),
    ]);

    $job = new ProcessChatMessage($this->conversation, $this->project, 'Should I use PostgreSQL?', []);

    app()->call([$job, 'handle']);

    Event::assertDispatched(AgentsProcessing::class, function ($event) {
        return count($event->agents) === 1 && $event->agents[0]['name'] === 'Architect';
    });

    Queue::assertPushed(ProcessAgentMessage::class, 1);
});

test('it dispatches directly when agent ids provided', function () {
    Queue::fake([ProcessAgentMessage::class]);
    Event::fake([AgentsProcessing::class]);

    $job = new ProcessChatMessage($this->conversation, $this->project, 'Hello', [$this->architect->id]);

    app()->call([$job, 'handle']);

    Event::assertDispatched(AgentsProcessing::class, function ($event) {
        return count($event->agents) === 1 && $event->agents[0]['name'] === 'Architect';
    });

    Queue::assertPushed(ProcessAgentMessage::class, 1);
});

test('it routes to multiple agents via moderator', function () {
    Queue::fake([ProcessAgentMessage::class]);
    Event::fake([AgentsProcessing::class]);

    ModeratorAgent::fake([
        json_encode([
            'agents' => [
                ['type' => 'architect', 'confidence' => 0.9],
                ['type' => 'analyst', 'confidence' => 0.85],
            ],
            'reasoning' => 'Both relevant.',
        ]),
    ]);

    $job = new ProcessChatMessage($this->conversation, $this->project, 'Analyze the architecture.', []);

    app()->call([$job, 'handle']);

    Event::assertDispatched(AgentsProcessing::class, function ($event) {
        return count($event->agents) === 2;
    });

    Queue::assertPushed(ProcessAgentMessage::class, 2);
});

test('it broadcasts enquete when no agent has high confidence', function () {
    Queue::fake([ProcessAgentMessage::class]);
    Event::fake([AgentsProcessing::class, AgentSelectionRequired::class]);

    ModeratorAgent::fake([
        json_encode([
            'agents' => [
                ['type' => 'analyst', 'confidence' => 0.6],
                ['type' => 'architect', 'confidence' => 0.5],
            ],
            'reasoning' => 'Uncertain.',
        ]),
    ]);

    $job = new ProcessChatMessage($this->conversation, $this->project, 'Tell me more.', []);
    app()->call([$job, 'handle']);

    Queue::assertNothingPushed();

    Event::assertDispatched(AgentSelectionRequired::class, function ($event) {
        return count($event->candidates) === 2;
    });
});

test('it throws when moderator returns no agents', function () {
    Queue::fake([ProcessAgentMessage::class]);
    Event::fake([AgentsProcessing::class, AgentSelectionRequired::class]);

    ModeratorAgent::fake([
        json_encode([
            'agents' => [],
            'reasoning' => 'No idea.',
        ]),
    ]);

    $job = new ProcessChatMessage($this->conversation, $this->project, 'Tell me something.', []);

    expect(fn () => app()->call([$job, 'handle']))->toThrow(\RuntimeException::class, 'Moderator returned no agents for routing.');
});

test('it has retry and timeout configuration', function () {
    $job = new ProcessChatMessage($this->conversation, $this->project, 'test');

    expect($job->tries)->toBe(2)
        ->and($job->timeout)->toBe(30)
        ->and($job->backoff)->toBe(3);
});

test('it broadcasts RoutingFailed when all retries exhausted', function () {
    Event::fake([RoutingFailed::class]);

    $job = new ProcessChatMessage($this->conversation, $this->project, 'test');
    $job->failed(new \RuntimeException('API timeout'));

    Event::assertDispatched(RoutingFailed::class, function ($event) {
        return $event->conversation->id === $this->conversation->id
            && $event->error === 'Failed to route your message. Please try again.';
    });
});
