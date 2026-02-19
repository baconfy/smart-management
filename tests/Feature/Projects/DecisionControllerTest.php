<?php

declare(strict_types=1);

use App\Enums\DecisionStatus;
use App\Models\Project;
use App\Models\User;

test('guest cannot view decisions', function (): void {
    $project = Project::create(['name' => 'Test']);

    $this->get(route('projects.decisions.index', $project))->assertRedirect('/login');
});

test('non-member cannot view decisions', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);

    $this->actingAs($user)->get(route('projects.decisions.index', $project))->assertForbidden();
});

test('member can view decisions page', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->get(route('projects.decisions.index', $project))->assertOk()->assertInertia(fn ($page) => $page->component('projects/decisions/index'));
});

test('decisions are passed to the page', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $project->decisions()->create(['title' => 'Use PostgreSQL', 'choice' => 'PostgreSQL', 'reasoning' => 'Best fit.']);

    $project->decisions()->create(['title' => 'Use Redis', 'choice' => 'Redis', 'reasoning' => 'Fast.', 'status' => DecisionStatus::Superseded->value]);

    $this->actingAs($user)->get(route('projects.decisions.index', $project))->assertOk()->assertInertia(fn ($page) => $page->has('decisions', 2)->has('project'));
});

test('decisions only include current project', function (): void {
    $user = User::factory()->create();
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);
    $projectA->members()->create(['user_id' => $user->id, 'role' => 'owner']);
    $projectB->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $projectA->decisions()->create(['title' => 'A Decision', 'choice' => 'A', 'reasoning' => 'R']);
    $projectB->decisions()->create(['title' => 'B Decision', 'choice' => 'B', 'reasoning' => 'R']);

    $this->actingAs($user)->get(route('projects.decisions.index', $projectA))->assertOk()->assertInertia(fn ($page) => $page->has('decisions', 1));
});
