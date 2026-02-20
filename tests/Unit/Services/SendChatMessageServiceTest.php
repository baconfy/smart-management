<?php

declare(strict_types=1);

use App\Jobs\GenerateConversationTitle;
use App\Jobs\ProcessChatMessage;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Project;
use App\Models\User;
use App\Services\SendChatMessageService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    Queue::fake();

    $this->project = Project::create(['name' => 'Test']);
    $this->user = User::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
});

// ============================================================================
// New Conversation
// ============================================================================

test('it creates a new conversation when no conversation id provided', function (): void {
    $conversation = app(SendChatMessageService::class)(
        $this->project,
        $this->user,
        'Hello world',
    );

    expect($conversation)
        ->toBeInstanceOf(Conversation::class)
        ->project_id->toBe($this->project->id)
        ->user_id->toBe($this->user->id);
});

test('it stores the user message', function (): void {
    $conversation = app(SendChatMessageService::class)(
        $this->project,
        $this->user,
        'What database should I use?',
    );

    $message = ConversationMessage::where('conversation_id', $conversation->id)->first();

    expect($message)
        ->role->toBe('user')
        ->content->toBe('What database should I use?')
        ->user_id->toBe($this->user->id);
});

test('it dispatches ProcessChatMessage job', function (): void {
    app(SendChatMessageService::class)(
        $this->project,
        $this->user,
        'Hello',
    );

    Queue::assertPushed(ProcessChatMessage::class);
});

test('it dispatches GenerateConversationTitle for new conversations', function (): void {
    app(SendChatMessageService::class)(
        $this->project,
        $this->user,
        'Hello',
    );

    Queue::assertPushed(GenerateConversationTitle::class);
});

test('it sets conversation title from message preview', function (): void {
    $conversation = app(SendChatMessageService::class)(
        $this->project,
        $this->user,
        'What database should I use for this project?',
    );

    expect($conversation->title)->toBe('What database should I use for this project?');
});

// ============================================================================
// Existing Conversation
// ============================================================================

test('it uses existing conversation when id provided', function (): void {
    $existing = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'title' => 'Existing conversation',
    ]);

    $conversation = app(SendChatMessageService::class)(
        $this->project,
        $this->user,
        'Follow up message',
        $existing->id,
    );

    expect($conversation->id)->toBe($existing->id);
});

test('it does not dispatch GenerateConversationTitle for existing conversations', function (): void {
    $existing = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'title' => 'Existing',
    ]);

    app(SendChatMessageService::class)(
        $this->project,
        $this->user,
        'Follow up',
        $existing->id,
    );

    Queue::assertNotPushed(GenerateConversationTitle::class);
});

// ============================================================================
// Agent IDs
// ============================================================================

test('it passes agent ids to ProcessChatMessage job', function (): void {
    $agent = $this->project->agents()->create([
        'type' => 'custom',
        'name' => 'Agent',
        'instructions' => 'Do things.',
    ]);

    app(SendChatMessageService::class)(
        $this->project,
        $this->user,
        'Hello',
        null,
        [$agent->id],
    );

    Queue::assertPushed(ProcessChatMessage::class, function ($job) use ($agent) {
        return $job->agentIds === [$agent->id];
    });
});
