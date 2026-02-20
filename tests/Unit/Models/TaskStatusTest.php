<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\TaskStatus;
use Illuminate\Database\QueryException;

// ============================================================================
// Creation
// ============================================================================

test('can create a project status with required fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $status = $project->statuses()->create([
        'name' => 'To Do',
        'slug' => 'todo',
        'color' => '#71717a',
        'position' => 0,
    ]);

    $status->refresh();

    expect($status)
        ->toBeInstanceOf(TaskStatus::class)
        ->name->toBe('To Do')
        ->slug->toBe('todo')
        ->color->toBe('#71717a')
        ->position->toBe(0)
        ->is_default->toBeFalse()
        ->is_closed->toBeFalse();
});

test('project status casts booleans correctly', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $status = $project->statuses()->create(['name' => 'Done', 'slug' => 'done', 'position' => 2, 'is_default' => false, 'is_closed' => true]);

    expect($status)
        ->is_default->toBeFalse()
        ->is_closed->toBeTrue();
});

// ============================================================================
// Relationships
// ============================================================================

test('project status belongs to project', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $status = $project->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0]);

    expect($status->project)
        ->toBeInstanceOf(Project::class)
        ->id->toBe($project->id);
});

test('project has many statuses', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $project->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0]);
    $project->statuses()->create(['name' => 'Done', 'slug' => 'done', 'position' => 1]);

    expect($project->statuses)->toHaveCount(2);
});

test('project status has many tasks', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $status = $project->statuses()->create([
        'name' => 'To Do',
        'slug' => 'todo',
        'position' => 0,
    ]);

    $project->tasks()->create([
        'title' => 'Task 1',
        'description' => 'D',
        'task_status_id' => $status->id,
    ]);

    $project->tasks()->create([
        'title' => 'Task 2',
        'description' => 'D',
        'task_status_id' => $status->id,
    ]);

    expect($status->tasks)->toHaveCount(2);
});

// ============================================================================
// Constraints
// ============================================================================

test('slug is unique per project', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $project->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0]);
    $project->statuses()->create(['name' => 'To Do Again', 'slug' => 'todo', 'position' => 1]);
})->throws(QueryException::class);

test('same slug allowed on different projects', function (): void {
    $projectA = Project::factory()->create(['name' => 'A']);
    $projectB = Project::factory()->create(['name' => 'B']);

    $projectA->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0]);
    $projectB->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0]);

    expect(TaskStatus::where('slug', 'todo')->count())->toBe(2);
});

test('deleting status is restricted when tasks exist', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $status = $project->statuses()->create([
        'name' => 'To Do',
        'slug' => 'todo',
        'position' => 0,
    ]);

    $project->tasks()->create([
        'title' => 'Task',
        'description' => 'D',
        'task_status_id' => $status->id,
    ]);

    $status->delete();
})->throws(QueryException::class);

test('statuses are deleted when project is force deleted', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $project->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0]);
    $project->statuses()->create(['name' => 'Done', 'slug' => 'done', 'position' => 1]);

    $project->forceDelete();

    expect(TaskStatus::count())->toBe(0);
});

test('statuses persist when project is soft deleted', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $project->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0]);
    $project->statuses()->create(['name' => 'Done', 'slug' => 'done', 'position' => 1]);

    $project->delete();

    expect(TaskStatus::count())->toBe(2);
});

// ============================================================================
// Scopes
// ============================================================================

test('default scope returns the default status', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $project->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0, 'is_default' => true]);
    $project->statuses()->create(['name' => 'Done', 'slug' => 'done', 'position' => 1, 'is_default' => false]);

    $defaults = $project->statuses()->default()->get();

    expect($defaults)->toHaveCount(1)
        ->and($defaults->first()->slug)->toBe('todo');
});

test('closed scope returns closed statuses', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $project->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0, 'is_closed' => false]);
    $project->statuses()->create(['name' => 'Done', 'slug' => 'done', 'position' => 1, 'is_closed' => true]);

    $closed = $project->statuses()->closed()->get();

    expect($closed)->toHaveCount(1)
        ->and($closed->first()->slug)->toBe('done');
});

test('ordered scope returns statuses by position', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);

    $project->statuses()->create(['name' => 'Done', 'slug' => 'done', 'position' => 2]);
    $project->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0]);
    $project->statuses()->create(['name' => 'In Progress', 'slug' => 'in_progress', 'position' => 1]);

    $slugs = $project->statuses()->ordered()->pluck('slug')->toArray();

    expect($slugs)->toBe(['todo', 'in_progress', 'done']);
});
