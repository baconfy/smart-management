<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

test('guest cannot view project conversations', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $this->get(route('projects.conversations.index', $project))
        ->assertRedirect('/login');
});

test('non-member cannot view project conversations', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.conversations.index', $project))
        ->assertForbidden();
});

test('member can view conversations index', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->get(route('projects.conversations.index', $project))->assertOk()->assertInertia(
        fn ($page) => $page->component('projects/conversations/index')->has('project')->has('agents')
    );
});
