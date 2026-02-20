<?php

declare(strict_types=1);

use App\Enums\AgentType;
use App\Events\AgentsProcessing;
use App\Jobs\ProcessAgentMessage;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use App\Services\DispatchAgentsService;
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
});

test('it dispatches AgentsProcessing event', function (): void {
    Queue::fake();
    Event::fake([AgentsProcessing::class]);

    $agent = $this->project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);

    app(DispatchAgentsService::class)($this->conversation, collect([$agent]), 'Hello');

    Event::assertDispatched(AgentsProcessing::class, function ($event) {
        return count($event->agents) === 1 && $event->agents[0]['name'] === 'Architect';
    });
});

test('it dispatches ProcessAgentMessage for each agent', function (): void {
    Queue::fake([ProcessAgentMessage::class]);
    Event::fake([AgentsProcessing::class]);

    $architect = $this->project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'Architect.',
    ]);

    $analyst = $this->project->agents()->create([
        'type' => AgentType::Analyst->value,
        'name' => 'Analyst',
        'instructions' => 'Analyst.',
    ]);

    app(DispatchAgentsService::class)($this->conversation, collect([$architect, $analyst]), 'Hello');

    Queue::assertPushed(ProcessAgentMessage::class, 2);
});

test('it sends correct agent data in the event', function (): void {
    Queue::fake();
    Event::fake([AgentsProcessing::class]);

    $architect = $this->project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'Architect.',
    ]);

    $analyst = $this->project->agents()->create([
        'type' => AgentType::Analyst->value,
        'name' => 'Analyst',
        'instructions' => 'Analyst.',
    ]);

    app(DispatchAgentsService::class)($this->conversation, collect([$architect, $analyst]), 'Hello');

    Event::assertDispatched(AgentsProcessing::class, function ($event) use ($architect, $analyst) {
        return $event->agents[0]['id'] === $architect->id
            && $event->agents[0]['name'] === 'Architect'
            && $event->agents[1]['id'] === $analyst->id
            && $event->agents[1]['name'] === 'Analyst';
    });
});
