<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

test('guest cannot view tasks', function (): void {
    $project = Project::create(['name' => 'Test']);

    $this->getJson(route('projects.tasks.index', $project))->assertUnauthorized();
});

test('non-member cannot view tasks', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $other->id, 'role' => 'owner']);

    $this->actingAs($user)->get(route('projects.tasks.index', $project))->assertForbidden();
});

test('member can view tasks', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $project->tasks()->create(['title' => 'Setup DB', 'description' => 'Create schema.']);

    $this->actingAs($user)->get(route('projects.tasks.index', $project))->assertOk()->assertInertia(
        fn ($page) => $page->component('projects/tasks/index')->has('tasks', 1)->where('tasks.0.title', 'Setup DB')
    );
});

test('tasks are scoped to project', function (): void {
    $user = User::factory()->create();
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);
    $projectA->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $projectA->tasks()->create(['title' => 'Task A', 'description' => 'D']);
    $projectB->tasks()->create(['title' => 'Task B', 'description' => 'D']);

    $this->actingAs($user)->get(route('projects.tasks.index', $projectA))->assertInertia(
        fn ($page) => $page->has('tasks', 1)->where('tasks.0.title', 'Task A')
    );
});

test('empty tasks returns empty array', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $this->actingAs($user)->get(route('projects.tasks.index', $project))->assertInertia(
        fn ($page) => $page->has('tasks', 0)
    );
});

// ============================================================================
// Task Detail (Show)
// ============================================================================

test('member can view task detail', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $task = $project->tasks()->create(['title' => 'Setup DB', 'description' => 'Create schema.']);
    $task->implementationNotes()->create(['title' => 'Note 1', 'content' => 'Use PostgreSQL.']);

    $this->actingAs($user)->get(route('projects.tasks.show', [$project, $task]))->assertOk()->assertInertia(
        fn ($page) => $page->component('projects/tasks/show')->where('task.title', 'Setup DB')->has('implementationNotes', 1)->where('implementationNotes.0.title', 'Note 1')
    );
});

test('task detail includes subtasks', function (): void {
    $user = User::factory()->create();
    $project = Project::create(['name' => 'Test']);
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $parent = $project->tasks()->create(['title' => 'Parent', 'description' => 'D']);
    $parent->subtasks()->create(['title' => 'Subtask', 'description' => 'D', 'project_id' => $project->id]);

    $this->actingAs($user)->get(route('projects.tasks.show', [$project, $parent]))->assertInertia(
        fn ($page) => $page->has('subtasks', 1)->where('subtasks.0.title', 'Subtask')
    );
});

test('task from another project returns 404', function (): void {
    $user = User::factory()->create();
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);
    $projectA->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $task = $projectB->tasks()->create(['title' => 'Other', 'description' => 'D']);

    $this->actingAs($user)->get(route('projects.tasks.show', [$projectA, $task]))->assertNotFound();
});
