<?php

declare(strict_types=1);

use App\Actions\Projects\RemoveProjectMember;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

test('it removes a project member', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $user = User::factory()->create();

    $member = $project->members()->create(['user_id' => $user->id, 'role' => 'member']);

    $result = (new RemoveProjectMember)($member);

    expect($result)->toBeTrue();
    expect(ProjectMember::count())->toBe(0);
});

test('it only removes the specified member', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $memberA = $project->members()->create(['user_id' => $userA->id, 'role' => 'member']);
    $project->members()->create(['user_id' => $userB->id, 'role' => 'member']);

    (new RemoveProjectMember)($memberA);

    expect(ProjectMember::count())->toBe(1);
    expect(ProjectMember::first()->user_id)->toBe($userB->id);
});
