<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

test('guest cannot view a project', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $this->get(route('projects.show', $project))->assertRedirect('/login');
});

test('member can view their project', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'My Project']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->get(route('projects.show', $project))->assertOk()->assertInertia(
        fn ($page) => $page->component('projects/show')->where('project.name', 'My Project')->where('project.ulid', $project->ulid)
    );
});

test('non-member cannot view a project', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->create(['name' => 'Not Mine']);
    $project->members()->create(['user_id' => $other->id, 'role' => 'owner']);

    $this->actingAs($user)->get(route('projects.show', $project))->assertForbidden();
});

test('show returns 404 for invalid ulid', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/projects/01INVALID_ULID')->assertNotFound();
});
