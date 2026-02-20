<?php

declare(strict_types=1);

use App\Actions\ConversationMessages\CreateConversationMessage;
use App\Models\ConversationMessage;
use App\Models\Project;
use App\Models\User;

test('it creates a message for a conversation', function (): void {
    $project = Project::create(['name' => 'Test']);
    $user = User::factory()->create();

    $conversation = $project->conversations()->create([
        'id' => str()->ulid()->toBase32(),
        'user_id' => $user->id,
        'title' => 'Chat',
    ]);

    $message = (new CreateConversationMessage)($conversation, [
        'id' => str()->ulid()->toBase32(),
        'user_id' => $user->id,
        'role' => 'user',
        'content' => 'Hello, world!',
    ]);

    expect($message)
        ->toBeInstanceOf(ConversationMessage::class)
        ->conversation_id->toBe($conversation->id)
        ->user_id->toBe($user->id)
        ->role->toBe('user')
        ->content->toBe('Hello, world!');
});

test('it scopes to the given conversation', function (): void {
    $project = Project::create(['name' => 'Test']);
    $user = User::factory()->create();

    $convA = $project->conversations()->create(['id' => str()->ulid()->toBase32(), 'user_id' => $user->id, 'title' => 'A']);
    $convB = $project->conversations()->create(['id' => str()->ulid()->toBase32(), 'user_id' => $user->id, 'title' => 'B']);

    (new CreateConversationMessage)($convA, ['id' => str()->ulid()->toBase32(), 'user_id' => $user->id, 'role' => 'user', 'content' => 'Msg A']);
    (new CreateConversationMessage)($convB, ['id' => str()->ulid()->toBase32(), 'user_id' => $user->id, 'role' => 'user', 'content' => 'Msg B']);

    expect($convA->messages)->toHaveCount(1);
    expect($convA->messages->first()->content)->toBe('Msg A');
});
