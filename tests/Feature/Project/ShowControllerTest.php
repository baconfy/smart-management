<?php

declare(strict_types=1);

use App\Models\BusinessRule;
use App\Models\Conversation;
use App\Models\Decision;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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

test('show returns aggregated counts', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $openStatus = TaskStatus::factory()->create(['project_id' => $project->id, 'is_closed' => false]);
    $closedStatus = TaskStatus::factory()->closed()->create(['project_id' => $project->id]);

    Task::factory()->count(3)->create(['project_id' => $project->id, 'task_status_id' => $openStatus->id]);
    Task::factory()->count(2)->create(['project_id' => $project->id, 'task_status_id' => $closedStatus->id]);

    Decision::factory()->count(2)->create(['project_id' => $project->id]);
    Decision::factory()->superseded()->create(['project_id' => $project->id]);

    BusinessRule::factory()->create(['project_id' => $project->id]);
    BusinessRule::factory()->deprecated()->create(['project_id' => $project->id]);

    Conversation::factory()->count(3)->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->where('project.tasks_count', 5)
            ->where('project.tasks_open_count', 3)
            ->where('project.tasks_closed_count', 2)
            ->where('project.decisions_count', 2)
            ->where('project.business_rules_count', 1)
            ->where('project.conversations_count', 3)
        );
});

test('show returns recent tasks and decisions', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);

    $status = TaskStatus::factory()->create(['project_id' => $project->id]);
    Task::factory()->count(3)->create(['project_id' => $project->id, 'task_status_id' => $status->id]);

    Decision::factory()->count(2)->create(['project_id' => $project->id]);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->has('project.tasks', 3)
            ->has('project.decisions', 2)
        );
});
