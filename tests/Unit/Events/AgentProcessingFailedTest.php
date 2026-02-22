<?php

declare(strict_types=1);

use App\Events\AgentProcessingFailed;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->project = Project::factory()->create(['name' => 'Test Project']);
    $this->user = User::factory()->create();

    $this->conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'title' => 'Test',
    ]);
});

test('it broadcasts on the correct private channel', function () {
    $event = new AgentProcessingFailed($this->conversation, 1, 'Architect', 'Something went wrong.');

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe("private-conversation.{$this->conversation->id}");
});

test('it uses the correct broadcast event name', function () {
    $event = new AgentProcessingFailed($this->conversation, 1, 'Architect', 'Something went wrong.');

    expect($event->broadcastAs())->toBe('agent.processing.failed');
});

test('it includes agent and error data in broadcast payload', function () {
    $event = new AgentProcessingFailed($this->conversation, 42, 'Analyst', 'The agent failed.');

    $data = $event->broadcastWith();

    expect($data)
        ->toHaveKeys(['agent_id', 'agent_name', 'error'])
        ->and($data['agent_id'])->toBe(42)
        ->and($data['agent_name'])->toBe('Analyst')
        ->and($data['error'])->toBe('The agent failed.');
});

test('it can be dispatched as a broadcast event', function () {
    Event::fake([AgentProcessingFailed::class]);

    event(new AgentProcessingFailed($this->conversation, 1, 'Architect', 'Error.'));

    Event::assertDispatched(AgentProcessingFailed::class);
});
