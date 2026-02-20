<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::create(['name' => 'Test Project']);

    $this->conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'title' => 'Test Conversation',
    ]);
});

// ============================================================================
// Message Creation
// ============================================================================

test('can create a conversation message with required fields', function (): void {
    $message = ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'Hello, how can I structure this project?',
    ]);

    expect($message)
        ->toBeInstanceOf(ConversationMessage::class)
        ->role->toBe('user')
        ->content->toBe('Hello, how can I structure this project?');
});

test('message uses string primary key', function (): void {
    $ulid = (string) Str::ulid();

    $message = ConversationMessage::create([
        'id' => $ulid,
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'Test message.',
    ]);

    expect($message->id)->toBe($ulid)
        ->and($message->incrementing)->toBeFalse()
        ->and($message->getKeyType())->toBe('string');
});

// ============================================================================
// Array Casts
// ============================================================================

test('attachments are cast to array', function (): void {
    $attachments = [
        ['type' => 'image', 'url' => 'https://example.com/screenshot.png'],
        ['type' => 'file', 'url' => 'https://example.com/document.pdf'],
    ];

    $message = ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'Check these files.',
        'attachments' => $attachments,
    ]);

    expect($message->attachments)
        ->toBeArray()
        ->toHaveCount(2)
        ->and($message->attachments[0]['type'])->toBe('image');
});

test('tool calls are cast to array', function (): void {
    $toolCalls = [
        ['id' => 'call_1', 'name' => 'CreateTask', 'arguments' => ['title' => 'New task']],
    ];

    $message = ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'assistant',
        'content' => 'Creating a task for you.',
        'tool_calls' => $toolCalls,
    ]);

    expect($message->tool_calls)
        ->toBeArray()
        ->toHaveCount(1)
        ->and($message->tool_calls[0]['name'])->toBe('CreateTask');
});

test('tool results are cast to array', function (): void {
    $toolResults = [
        ['id' => 'call_1', 'result' => 'Task created: "New task" (ID: 42)'],
    ];

    $message = ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'assistant',
        'content' => 'Done.',
        'tool_results' => $toolResults,
    ]);

    expect($message->tool_results)
        ->toBeArray()
        ->toHaveCount(1)
        ->and($message->tool_results[0]['result'])->toContain('Task created');
});

test('usage is cast to array', function (): void {
    $usage = ['input_tokens' => 150, 'output_tokens' => 300];

    $message = ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'assistant',
        'content' => 'Response.',
        'usage' => $usage,
    ]);

    expect($message->usage)
        ->toBeArray()
        ->toBe($usage);
});

test('meta is cast to array', function (): void {
    $meta = ['model' => 'claude-sonnet-4-5-20250929', 'temperature' => 0.7];

    $message = ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'assistant',
        'content' => 'Response.',
        'meta' => $meta,
    ]);

    expect($message->meta)
        ->toBeArray()
        ->toBe($meta);
});

test('nullable json fields default to null', function (): void {
    $message = ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'Simple message.',
    ]);

    expect($message)
        ->attachments->toBeNull()
        ->tool_calls->toBeNull()
        ->tool_results->toBeNull()
        ->usage->toBeNull()
        ->meta->toBeNull();
});

// ============================================================================
// Relationships
// ============================================================================

test('message belongs to a conversation', function (): void {
    $message = ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'Test.',
    ]);

    expect($message->conversation)
        ->toBeInstanceOf(Conversation::class)
        ->id->toBe($this->conversation->id);
});

test('conversation has many messages', function (): void {
    ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'First message.',
    ]);

    ConversationMessage::create([
        'id' => (string) Str::ulid(),
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'role' => 'assistant',
        'content' => 'Response.',
    ]);

    expect($this->conversation->messages)->toHaveCount(2);
});

// ============================================================================
// Table Configuration
// ============================================================================

test('uses the correct table name', function (): void {
    $message = new ConversationMessage;

    expect($message->getTable())->toBe('agent_conversation_messages');
});
