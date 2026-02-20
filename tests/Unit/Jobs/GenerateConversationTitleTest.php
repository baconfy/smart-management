<?php

declare(strict_types=1);

use App\Events\ConversationTitleUpdated;
use App\Jobs\GenerateConversationTitle;
use App\Models\Conversation;
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

test('it generates a title and updates the conversation', function () {
    Event::fake([ConversationTitleUpdated::class]);

    $job = new GenerateConversationTitle($this->conversation);

    app()->call([$job, 'handle']);

    $this->conversation->refresh();

    expect($this->conversation->title)->not->toBe('Should I use PostgreSQL or MySQL for this payment gateway project?');
});

test('it broadcasts ConversationTitleUpdated after saving', function () {
    Event::fake([ConversationTitleUpdated::class]);

    $job = new GenerateConversationTitle($this->conversation);

    app()->call([$job, 'handle']);

    Event::assertDispatched(ConversationTitleUpdated::class, function ($event) {
        return $event->conversation->id === $this->conversation->id;
    });
});

test('it is queued', function () {
    expect(GenerateConversationTitle::class)->toImplement(ShouldQueue::class);
});
