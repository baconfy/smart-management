<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

test('guest cannot list projects', function (): void {
    $this->get(route('projects.index'))->assertRedirect('/login');
});

test('authenticated user can list projects', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('projects.index'))->assertOk()->assertInertia(fn ($page) => $page->component('projects/index'));
});

test('index only shows projects the user belongs to', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    // User's project
    $userProject = Project::factory()->create(['name' => 'My Project']);
    $userProject->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    // Other's project
    $otherProject = Project::factory()->create(['name' => 'Not Mine']);
    $otherProject->members()->create(['user_id' => $other->id, 'role' => 'owner']);

    $this->actingAs($user)->get(route('projects.index'))->assertInertia(
        fn ($page) => $page->component('projects/index')->has('projects', 1)->where('projects.0.name', 'My Project')
    );
});
