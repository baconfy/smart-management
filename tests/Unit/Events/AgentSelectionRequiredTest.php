<?php

declare(strict_types=1);

use App\Events\AgentSelectionRequired;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->project = Project::create(['name' => 'Test Project']);
    $this->user = User::factory()->create();

    $this->conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'title' => 'Test',
    ]);
});

test('it broadcasts on the correct private channel', function () {
    $event = new AgentSelectionRequired($this->conversation, [
        ['id' => 1, 'name' => 'Architect', 'confidence' => 0.6],
    ], 'Could be multiple agents.');

    $channel = $event->broadcastOn();

    expect($channel)
        ->toBeInstanceOf(PrivateChannel::class)
        ->and($channel->name)->toBe("private-conversation.{$this->conversation->id}");
});

test('it includes candidates and reasoning in broadcast payload', function () {
    $candidates = [
        ['id' => 1, 'name' => 'Architect', 'confidence' => 0.6],
        ['id' => 2, 'name' => 'Analyst', 'confidence' => 0.5],
    ];

    $event = new AgentSelectionRequired($this->conversation, $candidates, 'Message is ambiguous.');

    $data = $event->broadcastWith();

    expect($data)
        ->toHaveKey('candidates')
        ->toHaveKey('reasoning')
        ->and($data['candidates'])->toHaveCount(2)
        ->and($data['candidates'][0]['name'])->toBe('Architect')
        ->and($data['reasoning'])->toBe('Message is ambiguous.');
});

test('it uses the correct broadcast event name', function () {
    $event = new AgentSelectionRequired($this->conversation, [], 'Test.');

    expect($event->broadcastAs())->toBe('agent.selection.required');
});
