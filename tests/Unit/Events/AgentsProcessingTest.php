<?php

declare(strict_types=1);

use App\Events\AgentsProcessing;
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
    $event = new AgentsProcessing($this->conversation, [
        ['id' => 1, 'name' => 'Architect'],
        ['id' => 2, 'name' => 'PM'],
    ]);

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe("private-conversation.{$this->conversation->id}");
});

test('it includes agent list in broadcast payload', function () {
    $event = new AgentsProcessing($this->conversation, [
        ['id' => 1, 'name' => 'Architect'],
        ['id' => 2, 'name' => 'PM'],
    ]);

    $data = $event->broadcastWith();

    expect($data)
        ->toHaveKey('agents')
        ->and($data['agents'])->toHaveCount(2)
        ->and($data['agents'][0])->toBe(['id' => 1, 'name' => 'Architect'])
        ->and($data['agents'][1])->toBe(['id' => 2, 'name' => 'PM']);
});

test('it uses the correct broadcast event name', function () {
    $event = new AgentsProcessing($this->conversation, [
        ['id' => 1, 'name' => 'Architect'],
    ]);

    expect($event->broadcastAs())->toBe('agents.processing');
});
