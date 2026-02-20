<?php

declare(strict_types=1);

use App\Actions\Projects\SeedProjectStatuses;
use App\Models\Project;

test('seed project statuses creates 3 defaults', function (): void {
    $project = Project::create(['name' => 'Test']);

    (new SeedProjectStatuses)($project);

    $statuses = $project->statuses()->ordered()->get();

    expect($statuses)->toHaveCount(3);
    expect($statuses[0])->name->toBe('To Do')->slug->toBe('todo')->is_default->toBeTrue()->is_closed->toBeFalse();
    expect($statuses[1])->name->toBe('In Progress')->slug->toBe('in_progress')->is_default->toBeFalse()->is_closed->toBeFalse();
    expect($statuses[2])->name->toBe('Done')->slug->toBe('done')->is_default->toBeFalse()->is_closed->toBeTrue();
});

test('seed project statuses assigns correct colors', function (): void {
    $project = Project::create(['name' => 'Test']);

    (new SeedProjectStatuses)($project);

    $colors = $project->statuses()->ordered()->pluck('color')->toArray();

    expect($colors)->toBe(['#71717a', '#3b82f6', '#22c55e']);
});

test('seed project statuses assigns sequential positions', function (): void {
    $project = Project::create(['name' => 'Test']);

    (new SeedProjectStatuses)($project);

    $positions = $project->statuses()->ordered()->pluck('position')->toArray();

    expect($positions)->toBe([0, 1, 2]);
});

test('seed project statuses marks only one as default', function (): void {
    $project = Project::create(['name' => 'Test']);

    (new SeedProjectStatuses)($project);

    expect($project->statuses()->default()->count())->toBe(1);
    expect($project->statuses()->default()->first()->slug)->toBe('todo');
});

test('seed project statuses marks only done as closed', function (): void {
    $project = Project::create(['name' => 'Test']);

    (new SeedProjectStatuses)($project);

    expect($project->statuses()->closed()->count())->toBe(1);
    expect($project->statuses()->closed()->first()->slug)->toBe('done');
});
