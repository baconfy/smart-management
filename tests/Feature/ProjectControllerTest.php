<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

// ============================================================================
// Index
// ============================================================================

test('guest cannot list projects', function (): void {
    $this->get('/projects')->assertRedirect('/login');
});

test('authenticated user can list projects', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/projects')->assertOk()->assertInertia(fn ($page) => $page->component('projects/index'));
});

test('index only shows projects the user belongs to', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    // User's project
    $userProject = Project::create(['name' => 'My Project']);
    $userProject->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    // Other's project
    $otherProject = Project::create(['name' => 'Not Mine']);
    $otherProject->members()->create(['user_id' => $other->id, 'role' => 'owner']);

    $this->actingAs($user)->get('/projects')->assertInertia(
        fn ($page) => $page->component('projects/index')->has('projects', 1)->where('projects.0.name', 'My Project')
    );
});

// ============================================================================
// Store
// ============================================================================

test('guest cannot create a project', function (): void {
    $this->post('/projects', ['name' => 'Test'])->assertRedirect('/login');
});

test('authenticated user can create a project', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/projects', ['name' => 'Arkham District', 'description' => 'Cryptocurrency payment gateway'])->assertRedirect();

    $project = Project::first();

    expect($project)
        ->name->toBe('Arkham District')
        ->description->toBe('Cryptocurrency payment gateway')
        ->ulid->not->toBeNull();
});

test('store redirects to project show page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', ['name' => 'Test Project']);

    $project = Project::first();

    $response->assertRedirect("/projects/{$project->ulid}");
});

test('store adds the user as owner', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/projects', ['name' => 'Test Project']);

    $project = Project::first();

    expect($project->members()->where('user_id', $user->id)->first())
        ->role->toBe('owner');
});

test('store seeds default agents', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/projects', ['name' => 'Test Project']);

    $project = Project::first();

    expect($project->agents)->toHaveCount(4);
});

test('store requires a name', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/projects', ['name' => ''])->assertSessionHasErrors('name');
});

test('store name must be at most 255 characters', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/projects', ['name' => str_repeat('a', 256)])->assertSessionHasErrors('name');
});

test('store description is optional', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/projects', ['name' => 'No Description'])->assertRedirect();

    expect(Project::first()->description)->toBeNull();
});

// ============================================================================
// Show
// ============================================================================

test('guest cannot view a project', function (): void {
    $project = Project::create(['name' => 'Test']);

    $this->get("/projects/{$project->ulid}")->assertRedirect('/login');
});

test('member can view their project', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'My Project']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->get("/projects/{$project->ulid}")->assertOk()->assertInertia(
        fn ($page) => $page->component('projects/show')->where('project.name', 'My Project')->where('project.ulid', $project->ulid)
    );
});

test('non-member cannot view a project', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::create(['name' => 'Not Mine']);
    $project->members()->create(['user_id' => $other->id, 'role' => 'owner']);

    $this->actingAs($user)->get("/projects/{$project->ulid}")->assertForbidden();
});

test('show returns 404 for invalid ulid', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/projects/01INVALID_ULID')->assertNotFound();
});
