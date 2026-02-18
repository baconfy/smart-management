<?php

declare(strict_types=1);

use App\Events\ConversationTitleUpdated;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->project = Project::create(['name' => 'Test Project']);
    $this->user = User::factory()->create();

    $this->conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'title' => 'Architecture decisions for the payment gateway',
    ]);
});

test('it broadcasts on the correct private channel', function () {
    $event = new ConversationTitleUpdated($this->conversation);

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe("private-conversation.{$this->conversation->id}");
});

test('it includes conversation data in broadcast payload', function () {
    $event = new ConversationTitleUpdated($this->conversation);

    $data = $event->broadcastWith();

    expect($data)
        ->toHaveKeys(['id', 'title'])
        ->and($data['id'])->toBe($this->conversation->id)
        ->and($data['title'])->toBe('Architecture decisions for the payment gateway');
});

test('it uses the correct broadcast event name', function () {
    $event = new ConversationTitleUpdated($this->conversation);

    expect($event->broadcastAs())->toBe('title.updated');
});

test('it can be dispatched as a broadcast event', function () {
    Event::fake([ConversationTitleUpdated::class]);

    event(new ConversationTitleUpdated($this->conversation));

    Event::assertDispatched(ConversationTitleUpdated::class);
});
