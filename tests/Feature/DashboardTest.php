<?php

use App\Models\BusinessRule;
use App\Models\Conversation;
use App\Models\Decision;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard returns totals and projects props', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    ProjectMember::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('totals', fn (Assert $totals) => $totals
                ->where('projects', 1)
                ->where('tasks_open', 0)
                ->where('tasks_closed', 0)
                ->where('decisions', 0)
            )
            ->has('projects', 1)
        );
});

test('dashboard shows correct counts for a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    ProjectMember::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

    $openStatus = TaskStatus::factory()->create(['project_id' => $project->id, 'is_closed' => false]);
    $closedStatus = TaskStatus::factory()->closed()->create(['project_id' => $project->id]);

    Task::factory()->count(3)->create(['project_id' => $project->id, 'task_status_id' => $openStatus->id]);
    Task::factory()->count(2)->create(['project_id' => $project->id, 'task_status_id' => $closedStatus->id]);

    Decision::factory()->count(2)->create(['project_id' => $project->id]);
    Decision::factory()->superseded()->create(['project_id' => $project->id]);

    BusinessRule::factory()->count(1)->create(['project_id' => $project->id]);
    BusinessRule::factory()->deprecated()->create(['project_id' => $project->id]);

    Conversation::factory()->count(3)->create(['project_id' => $project->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('totals', fn (Assert $totals) => $totals
                ->where('projects', 1)
                ->where('tasks_open', 3)
                ->where('tasks_closed', 2)
                ->where('decisions', 2)
            )
            ->has('projects', 1, fn (Assert $project) => $project
                ->where('tasks_count', 5)
                ->where('tasks_open_count', 3)
                ->where('tasks_closed_count', 2)
                ->where('decisions_count', 2)
                ->where('business_rules_count', 1)
                ->where('conversations_count', 3)
                ->etc()
            )
        );
});

test('dashboard only shows projects the user belongs to', function () {
    $user = User::factory()->create();
    $ownProject = Project::factory()->create();
    ProjectMember::factory()->create(['user_id' => $user->id, 'project_id' => $ownProject->id]);

    // Another user's project
    Project::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('totals', fn (Assert $totals) => $totals
                ->where('projects', 1)
                ->etc()
            )
            ->has('projects', 1)
        );
});
