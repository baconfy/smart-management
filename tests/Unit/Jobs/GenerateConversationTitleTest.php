<?php

declare(strict_types=1);

use App\Ai\Agents\ModeratorAgent;
use App\Events\ConversationTitleUpdated;
use App\Jobs\GenerateConversationTitle;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->project = Project::factory()->create(['name' => 'Test Project']);
    $this->user = User::factory()->create();

    $this->conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'title' => 'Should I use PostgreSQL or MySQL for this payment gateway project?',
    ]);
});

test('it generates a title from the first user message using ModeratorAgent', function () {
    Event::fake([ConversationTitleUpdated::class]);

    ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'Should I use PostgreSQL or MySQL for this payment gateway project?',
    ]);

    ModeratorAgent::fake(['Payment Gateway DB Choice']);

    $job = new GenerateConversationTitle($this->conversation);
    app()->call([$job, 'handle']);

    $this->conversation->refresh();

    expect($this->conversation->title)->toBe('Payment Gateway DB Choice');
});

test('it broadcasts ConversationTitleUpdated after saving', function () {
    Event::fake([ConversationTitleUpdated::class]);

    ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'Should I use PostgreSQL or MySQL?',
    ]);

    ModeratorAgent::fake(['DB Choice']);

    $job = new GenerateConversationTitle($this->conversation);
    app()->call([$job, 'handle']);

    Event::assertDispatched(ConversationTitleUpdated::class, function ($event) {
        return $event->conversation->id === $this->conversation->id;
    });
});

test('it falls back gracefully when conversation has no messages', function () {
    Event::fake([ConversationTitleUpdated::class]);

    $job = new GenerateConversationTitle($this->conversation);
    app()->call([$job, 'handle']);

    $this->conversation->refresh();

    // Title remains unchanged — no user messages to generate from
    expect($this->conversation->title)->toBe('Should I use PostgreSQL or MySQL for this payment gateway project?');
    Event::assertNotDispatched(ConversationTitleUpdated::class);
});

test('it falls back gracefully when AI call fails', function () {
    Event::fake([ConversationTitleUpdated::class]);

    ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'Hello world',
    ]);

    ModeratorAgent::fake(fn () => throw new \RuntimeException('API timeout'));

    $job = new GenerateConversationTitle($this->conversation);
    app()->call([$job, 'handle']);

    $this->conversation->refresh();

    // Title remains unchanged — AI call failed silently
    expect($this->conversation->title)->toBe('Should I use PostgreSQL or MySQL for this payment gateway project?');
    Event::assertNotDispatched(ConversationTitleUpdated::class);
});

test('it is queued', function () {
    expect(GenerateConversationTitle::class)->toImplement(ShouldQueue::class);
});

test('it has retry and timeout configuration', function () {
    $job = new GenerateConversationTitle($this->conversation);

    expect($job->tries)->toBe(2)
        ->and($job->timeout)->toBe(30);
});
