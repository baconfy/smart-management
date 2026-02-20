<?php

declare(strict_types=1);

use App\Actions\Conversations\CreateConversation;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;

test('it creates a conversation for a project', function (): void {
    $project = Project::create(['name' => 'Test']);
    $user = User::factory()->create();

    $conversation = (new CreateConversation)($project, [
        'id' => str()->ulid()->toBase32(),
        'user_id' => $user->id,
        'title' => 'Architecture Discussion',
    ]);

    expect($conversation)
        ->toBeInstanceOf(Conversation::class)
        ->project_id->toBe($project->id)
        ->user_id->toBe($user->id)
        ->title->toBe('Architecture Discussion');
});

test('it scopes to the given project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);
    $user = User::factory()->create();

    (new CreateConversation)($projectA, ['id' => str()->ulid()->toBase32(), 'user_id' => $user->id, 'title' => 'Conv A']);
    (new CreateConversation)($projectB, ['id' => str()->ulid()->toBase32(), 'user_id' => $user->id, 'title' => 'Conv B']);

    expect($projectA->conversations)->toHaveCount(1);
    expect($projectA->conversations->first()->title)->toBe('Conv A');
});
