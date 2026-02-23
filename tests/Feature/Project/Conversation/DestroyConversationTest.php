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

test('authenticated project member can delete a conversation', function () {
    $this->actingAs($this->user)
        ->delete(route('projects.conversations.destroy', [$this->project, $this->conversation]))
        ->assertRedirect(route('projects.conversations.index', $this->project));

    expect($this->conversation->fresh()->trashed())->toBeTrue();
});

test('guests cannot delete a conversation', function () {
    $this->delete(route('projects.conversations.destroy', [$this->project, $this->conversation]))
        ->assertRedirect(route('login'));
});

test('non-members cannot delete a conversation', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->delete(route('projects.conversations.destroy', [$this->project, $this->conversation]))
        ->assertForbidden();
});

test('cannot delete a conversation from another project', function () {
    $otherProject = Project::factory()->create();
    $otherProject->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);
    $otherConversation = Conversation::factory()->create(['project_id' => $otherProject->id, 'user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete(route('projects.conversations.destroy', [$this->project, $otherConversation]))
        ->assertNotFound();
});

test('deleting a conversation soft deletes it', function () {
    $this->actingAs($this->user)
        ->delete(route('projects.conversations.destroy', [$this->project, $this->conversation]));

    $this->assertSoftDeleted('agent_conversations', ['id' => $this->conversation->id]);
});
