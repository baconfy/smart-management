<?php

declare(strict_types=1);

use App\Actions\ConversationMessages\DeleteConversationMessage;
use App\Models\ConversationMessage;
use App\Models\Project;
use App\Models\User;

test('it deletes a conversation message', function (): void {
    $project = Project::create(['name' => 'Test']);
    $user = User::factory()->create();

    $conversation = $project->conversations()->create([
        'id' => str()->ulid()->toBase32(),
        'user_id' => $user->id,
        'title' => 'Chat',
    ]);

    $message = $conversation->messages()->create([
        'id' => str()->ulid()->toBase32(),
        'user_id' => $user->id,
        'role' => 'user',
        'content' => 'To Delete',
    ]);

    $result = (new DeleteConversationMessage)($message);

    expect($result)->toBeTrue();
    expect(ConversationMessage::count())->toBe(0);
});
