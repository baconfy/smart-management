<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->project = Project::factory()->create();
    $this->user = User::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    $this->conversation = Conversation::factory()->create(['project_id' => $this->project->id, 'user_id' => $this->user->id]);
});

test('authenticated project member can rename a conversation', function () {
    $this->actingAs($this->user)
        ->patch(route('projects.conversations.rename', [$this->project, $this->conversation]), ['title' => 'New Title'])
        ->assertRedirect();

    expect($this->conversation->fresh()->title)->toBe('New Title');
});

test('guests cannot rename a conversation', function () {
    $this->patch(route('projects.conversations.rename', [$this->project, $this->conversation]), ['title' => 'New Title'])
        ->assertRedirect(route('login'));
});

test('non-members cannot rename a conversation', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->patch(route('projects.conversations.rename', [$this->project, $this->conversation]), ['title' => 'New Title'])
        ->assertForbidden();
});

test('title is required when renaming', function () {
    $this->actingAs($this->user)
        ->patch(route('projects.conversations.rename', [$this->project, $this->conversation]), ['title' => ''])
        ->assertSessionHasErrors('title');
});

test('title cannot exceed 255 characters', function () {
    $this->actingAs($this->user)
        ->patch(route('projects.conversations.rename', [$this->project, $this->conversation]), ['title' => str_repeat('a', 256)])
        ->assertSessionHasErrors('title');
});

test('cannot rename a conversation from another project', function () {
    $otherProject = Project::factory()->create();
    $otherProject->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    $otherConversation = Conversation::factory()->create(['project_id' => $otherProject->id, 'user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->patch(route('projects.conversations.rename', [$this->project, $otherConversation]), ['title' => 'New Title'])
        ->assertNotFound();
});
