<?php

declare(strict_types=1);

use App\Events\RoutingFailed;
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
    $event = new RoutingFailed($this->conversation, 'Routing failed.');

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe("private-conversation.{$this->conversation->id}");
});

test('it uses the correct broadcast event name', function () {
    $event = new RoutingFailed($this->conversation, 'Routing failed.');

    expect($event->broadcastAs())->toBe('routing.failed');
});

test('it includes error data in broadcast payload', function () {
    $event = new RoutingFailed($this->conversation, 'Failed to route your message.');

    $data = $event->broadcastWith();

    expect($data)
        ->toHaveKeys(['error'])
        ->and($data['error'])->toBe('Failed to route your message.');
});

test('it can be dispatched as a broadcast event', function () {
    Event::fake([RoutingFailed::class]);

    event(new RoutingFailed($this->conversation, 'Error.'));

    Event::assertDispatched(RoutingFailed::class);
});
