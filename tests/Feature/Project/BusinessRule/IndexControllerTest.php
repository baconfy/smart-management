<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

test('guest cannot view business rules', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $this->getJson(route('projects.business-rules.index', $project))->assertUnauthorized();
});

test('non-member cannot view business rules', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $other->id, 'role' => 'owner']);

    $this->actingAs($user)->get(route('projects.business-rules.index', $project))->assertForbidden();
});

test('member can view business rules', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $project->businessRules()->create(['title' => 'Payment Rule', 'description' => 'Pay within 30 days.', 'category' => 'billing']);

    $this->actingAs($user)
        ->get(route('projects.business-rules.index', $project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/business-rules/index')
            ->has('businessRules', 1)
            ->where('businessRules.0.title', 'Payment Rule')
        );
});

test('business rules are scoped to project', function (): void {
    $user = User::factory()->create();
    $projectA = Project::factory()->create(['name' => 'A']);
    $projectB = Project::factory()->create(['name' => 'B']);
    $projectA->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $projectA->businessRules()->create(['title' => 'Rule A', 'description' => 'D', 'category' => 'billing']);
    $projectB->businessRules()->create(['title' => 'Rule B', 'description' => 'D', 'category' => 'billing']);

    $this->actingAs($user)
        ->get(route('projects.business-rules.index', $projectA))
        ->assertInertia(fn ($page) => $page
            ->has('businessRules', 1)
            ->where('businessRules.0.title', 'Rule A')
        );
});

test('empty business rules returns empty array', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)
        ->get(route('projects.business-rules.index', $project))
        ->assertInertia(fn ($page) => $page
            ->has('businessRules', 0)
        );
});
