<?php

declare(strict_types=1);

use App\Ai\Agents\ModeratorAgent;
use App\Enums\AgentType;
use App\Events\AgentSelectionRequired;
use App\Events\AgentsProcessing;
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

test('low confidence broadcasts AgentSelectionRequired instead of dispatching jobs', function () {
    Queue::fake([ProcessAgentMessage::class]);
    Event::fake([AgentSelectionRequired::class, AgentsProcessing::class]);

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
        return $event->conversation->id === $this->conversation->id
            && count($event->candidates) === 2
            && $event->candidates[0]['type'] === 'analyst'
            && $event->candidates[0]['confidence'] === 0.6;
    });

    Event::assertNotDispatched(AgentsProcessing::class);
});

test('high confidence still dispatches jobs normally', function () {
    Queue::fake([ProcessAgentMessage::class]);
    Event::fake([AgentSelectionRequired::class, AgentsProcessing::class]);

    ModeratorAgent::fake([
        json_encode([
            'agents' => [['type' => 'architect', 'confidence' => 0.95]],
            'reasoning' => 'Architecture question.',
        ]),
    ]);

    $job = new ProcessChatMessage($this->conversation, $this->project, 'Should I use PostgreSQL?', []);
    app()->call([$job, 'handle']);

    Queue::assertPushed(ProcessAgentMessage::class, 1);
    Event::assertDispatched(AgentsProcessing::class);
    Event::assertNotDispatched(AgentSelectionRequired::class);
});

test('mixed confidence dispatches only high confidence agents', function () {
    Queue::fake([ProcessAgentMessage::class]);
    Event::fake([AgentSelectionRequired::class, AgentsProcessing::class]);

    ModeratorAgent::fake([
        json_encode([
            'agents' => [
                ['type' => 'architect', 'confidence' => 0.9],
                ['type' => 'analyst', 'confidence' => 0.5],
            ],
            'reasoning' => 'Architect is clear, analyst uncertain.',
        ]),
    ]);

    $job = new ProcessChatMessage($this->conversation, $this->project, 'Design the database.', []);
    app()->call([$job, 'handle']);

    Queue::assertPushed(ProcessAgentMessage::class, 1);
    Event::assertDispatched(AgentsProcessing::class);
    Event::assertNotDispatched(AgentSelectionRequired::class);
});
