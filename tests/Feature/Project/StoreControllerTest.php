<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

test('guest cannot create a project', function (): void {
    $this->post(route('projects.store'), ['name' => 'Test'])->assertRedirect('/login');
});

test('authenticated user can create a project', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('projects.store'), ['name' => 'Arkham District', 'description' => 'Cryptocurrency payment gateway'])->assertRedirect();

    $project = Project::first();

    expect($project)
        ->name->toBe('Arkham District')
        ->description->toBe('Cryptocurrency payment gateway')
        ->ulid->not->toBeNull();
});

test('store redirects to project show page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store'), ['name' => 'Test Project']);

    $project = Project::first();

    $response->assertRedirect(route('projects.show', $project));
});

test('store adds the user as owner', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('projects.store'), ['name' => 'Test Project']);

    $project = Project::first();

    expect($project->members()->where('user_id', $user->id)->first())
        ->role->toBe('owner');
});

test('store seeds default agents', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('projects.store'), ['name' => 'Test Project']);

    $project = Project::first();

    expect($project->agents)->toHaveCount(5);
});

test('store requires a name', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('projects.store'), ['name' => ''])->assertSessionHasErrors('name');
});

test('store name must be at most 255 characters', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('projects.store'), ['name' => str_repeat('a', 256)])->assertSessionHasErrors('name');
});

test('store description is optional', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('projects.store'), ['name' => 'No Description'])->assertRedirect();

    expect(Project::first()->description)->toBeNull();
});
