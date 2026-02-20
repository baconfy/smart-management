<?php

declare(strict_types=1);

use App\Events\AgentMessageReceived;
use App\Models\Conversation;
use App\Models\ConversationMessage;
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
        'title' => 'Architecture Discussion',
    ]);

    $this->message = ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'assistant',
        'content' => 'Here is my analysis.',
        'agent' => 'App\\Ai\\Agents\\ArchitectAgent',
        'project_agent_id' => null,
    ]);
});

test('it broadcasts on the correct private channel', function () {
    $event = new AgentMessageReceived($this->message);

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe("private-conversation.{$this->conversation->id}");
});

test('it includes message data in broadcast payload', function () {
    $event = new AgentMessageReceived($this->message);

    $data = $event->broadcastWith();

    expect($data)
        ->toHaveKey('message')
        ->and($data['message'])
        ->toHaveKeys(['id', 'conversation_id', 'role', 'content', 'agent', 'project_agent_id', 'created_at']);
});

test('it uses the correct broadcast event name', function () {
    $event = new AgentMessageReceived($this->message);

    expect($event->broadcastAs())->toBe('message.received');
});

test('it can be dispatched as a broadcast event', function () {
    Event::fake([AgentMessageReceived::class]);

    event(new AgentMessageReceived($this->message));

    Event::assertDispatched(AgentMessageReceived::class);
});
