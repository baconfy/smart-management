<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['name' => 'Test']);
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);

    $this->conversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'title' => 'Test conversation',
    ]);
});

test('returns paginated messages for a conversation', function () {
    for ($i = 0; $i < 60; $i++) {
        ConversationMessage::create([
            'id' => (string) Str::ulid(),
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'role' => $i % 2 === 0 ? 'user' : 'assistant',
            'content' => "Message {$i}",
        ]);
    }

    $response = $this->actingAs($this->user)
        ->getJson(route('projects.conversations.messages', [$this->project, $this->conversation]));

    $response->assertOk()
        ->assertJsonCount(50, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'conversation_id', 'role', 'content']],
            'next_cursor',
            'next_page_url',
        ]);

    expect($response->json('next_cursor'))->not->toBeNull();
});

test('requires authentication', function () {
    $this->getJson(route('projects.conversations.messages', [$this->project, $this->conversation]))
        ->assertUnauthorized();
});

test('forbids non-members', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->getJson(route('projects.conversations.messages', [$this->project, $this->conversation]))
        ->assertForbidden();
});

test('returns 404 for conversation from another project', function () {
    $otherProject = Project::factory()->create(['name' => 'Other']);
    $otherProject->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);

    $otherConversation = Conversation::create([
        'id' => (string) Str::ulid(),
        'project_id' => $otherProject->id,
        'user_id' => $this->user->id,
        'title' => 'Other conversation',
    ]);

    $this->actingAs($this->user)
        ->getJson(route('projects.conversations.messages', [$this->project, $otherConversation]))
        ->assertNotFound();
});
