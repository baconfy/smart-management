<?php

declare(strict_types=1);

use App\Actions\Conversations\DeleteConversation;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;

test('it deletes a conversation', function (): void {
    $project = Project::create(['name' => 'Test']);
    $user = User::factory()->create();

    $conversation = $project->conversations()->create([
        'id' => str()->ulid()->toBase32(),
        'user_id' => $user->id,
        'title' => 'To Delete',
    ]);

    $result = (new DeleteConversation)($conversation);

    expect($result)->toBeTrue();
    expect(Conversation::count())->toBe(0);
});
