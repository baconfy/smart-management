<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Models\ImplementationNote;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;

// ============================================================================
// Task Creation
// ============================================================================

test('can create a task with required fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $status = $project->statuses()->create(['name' => 'Backlog', 'slug' => 'backlog', 'position' => 0]);

    $task = $project->tasks()->create([
        'title' => 'Implement HD Wallet Derivation',
        'description' => 'Create BIP44 derivation path for deposit addresses.',
        'task_status_id' => $status->id,
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    expect($task)
        ->toBeInstanceOf(Task::class)
        ->title->toBe('Implement HD Wallet Derivation')
        ->task_status_id->toBe($status->id)
        ->priority->toBe(TaskPriority::High)
        ->sort_order->toBe(1);
});

test('task has nullable optional fields', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Simple task',
        'description' => 'No extras.',
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    expect($task)
        ->phase->toBeNull()
        ->milestone->toBeNull()
        ->estimate->toBeNull()
        ->parent_task_id->toBeNull()
        ->task_status_id->toBeNull();
});

test('task stores phase and milestone', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Scanner implementation',
        'description' => 'Build blockchain scanner.',
        'phase' => 'Phase 2',
        'milestone' => 'Core Infrastructure',
        'estimate' => '8h',
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    expect($task)
        ->phase->toBe('Phase 2')
        ->milestone->toBe('Core Infrastructure')
        ->estimate->toBe('8h');
});

// ============================================================================
// Task Relationships
// ============================================================================

test('task belongs to project', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Test task',
        'description' => 'A task.',
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    expect($task->project)
        ->toBeInstanceOf(Project::class)
        ->id->toBe($project->id);
});

test('task belongs to project status', function (): void {
    $project = Project::factory()->create();
    $status = $project->statuses()->create(['name' => 'To Do', 'slug' => 'todo', 'position' => 0]);

    $task = $project->tasks()->create(['title' => 'Test task', 'description' => 'A task.', 'task_status_id' => $status->id, 'priority' => TaskPriority::Medium->value, 'sort_order' => 1]);

    expect($task->status)->toBeInstanceOf(TaskStatus::class)->id->toBe($status->id);
});

test('project has many tasks', function (): void {
    $project = Project::factory()->create();

    $project->tasks()->create(['title' => 'Task A', 'description' => 'First.', 'priority' => TaskPriority::High->value, 'sort_order' => 1]);
    $project->tasks()->create(['title' => 'Task B', 'description' => 'Second.', 'priority' => TaskPriority::Medium->value, 'sort_order' => 2]);

    expect($project->tasks)->toHaveCount(2);
});

// ============================================================================
// Subtasks
// ============================================================================

test('task can have subtasks', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $parent = $project->tasks()->create([
        'title' => 'Parent task',
        'description' => 'Has children.',
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $project->tasks()->create([
        'title' => 'Subtask A',
        'description' => 'Child A.',
        'parent_task_id' => $parent->id,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $project->tasks()->create([
        'title' => 'Subtask B',
        'description' => 'Child B.',
        'parent_task_id' => $parent->id,
        'priority' => TaskPriority::Low->value,
        'sort_order' => 2,
    ]);

    expect($parent->subtasks)->toHaveCount(2);
});

test('subtask belongs to parent', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $parent = $project->tasks()->create([
        'title' => 'Parent',
        'description' => 'Parent task.',
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $child = $project->tasks()->create([
        'title' => 'Child',
        'description' => 'Child task.',
        'parent_task_id' => $parent->id,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    expect($child->parent)
        ->toBeInstanceOf(Task::class)
        ->id->toBe($parent->id);
});

// ============================================================================
// Task Enums
// ============================================================================

test('all task priorities are valid', function (): void {
    $expected = ['high', 'medium', 'low'];

    $values = array_map(fn (TaskPriority $p) => $p->value, TaskPriority::cases());

    expect($values)->toBe($expected);
});

// ============================================================================
// Implementation Notes
// ============================================================================

test('can create an implementation note for a task', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'HD Wallet',
        'description' => 'Implement derivation.',
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $note = $task->implementationNotes()->create([
        'title' => 'Nonce management resolved',
        'content' => 'Used sequential queue to avoid race conditions in concurrent transfers.',
    ]);

    expect($note)
        ->toBeInstanceOf(ImplementationNote::class)
        ->title->toBe('Nonce management resolved');
});

test('implementation note stores code snippets as json', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Scanner',
        'description' => 'Build scanner.',
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $snippets = [
        ['language' => 'php', 'code' => '$wallet->derive($path);'],
        ['language' => 'sql', 'code' => 'SELECT * FROM wallets;'],
    ];

    $note = $task->implementationNotes()->create([
        'title' => 'Derivation approach',
        'content' => 'Used BIP44 standard path.',
        'code_snippets' => $snippets,
    ]);

    expect($note->code_snippets)
        ->toBeArray()
        ->toHaveCount(2);
});

test('implementation note belongs to task', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Test task',
        'description' => 'A task.',
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $note = $task->implementationNotes()->create([
        'title' => 'Note',
        'content' => 'Some content.',
    ]);

    expect($note->task)
        ->toBeInstanceOf(Task::class)
        ->id->toBe($task->id);
});

test('task has many implementation notes', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Complex task',
        'description' => 'Needs multiple notes.',
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $task->implementationNotes()->create(['title' => 'Note 1', 'content' => 'First attempt.']);
    $task->implementationNotes()->create(['title' => 'Note 2', 'content' => 'Second approach.']);

    expect($task->implementationNotes)->toHaveCount(2);
});

// ============================================================================
// Cascade Soft Delete
// ============================================================================

test('tasks are soft deleted when project is deleted', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $project->tasks()->create(['title' => 'Task', 'description' => 'Will be deleted.', 'priority' => TaskPriority::Medium->value, 'sort_order' => 1]);

    $project->delete();

    expect(Task::count())->toBe(0)
        ->and(Task::withTrashed()->count())->toBe(1);
});

test('implementation notes are soft deleted when task is deleted', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Task',
        'description' => 'Has notes.',
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $task->implementationNotes()->create(['title' => 'Note', 'content' => 'Content.']);
    $task->delete();

    expect(ImplementationNote::count())->toBe(0)
        ->and(ImplementationNote::withTrashed()->count())->toBe(1);
});

test('subtasks are soft deleted when parent task is deleted', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $parent = $project->tasks()->create([
        'title' => 'Parent',
        'description' => 'Has subtasks.',
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $project->tasks()->create([
        'title' => 'Subtask',
        'description' => 'Child.',
        'parent_task_id' => $parent->id,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $parent->delete();

    expect(Task::count())->toBe(0)
        ->and(Task::withTrashed()->count())->toBe(2);
});

// ============================================================================
// Cascade Restore
// ============================================================================

test('tasks are restored when project is restored', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $project->tasks()->create(['title' => 'Task', 'description' => 'Will be restored.', 'priority' => TaskPriority::Medium->value, 'sort_order' => 1]);

    $project->delete();
    $project->restore();

    expect(Task::count())->toBe(1);
});

test('implementation notes are restored when task is restored', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Task',
        'description' => 'Has notes.',
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $task->implementationNotes()->create(['title' => 'Note', 'content' => 'Content.']);
    $task->delete();
    $task->restore();

    expect(ImplementationNote::count())->toBe(1);
});

test('subtasks are restored when parent task is restored', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $parent = $project->tasks()->create([
        'title' => 'Parent',
        'description' => 'Has subtasks.',
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $project->tasks()->create([
        'title' => 'Subtask',
        'description' => 'Child.',
        'parent_task_id' => $parent->id,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $parent->delete();
    $parent->restore();

    expect(Task::count())->toBe(2);
});

// ============================================================================
// Cascade Force Delete
// ============================================================================

test('tasks are force deleted when project is force deleted', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $project->tasks()->create(['title' => 'Task', 'description' => 'Will be gone.', 'priority' => TaskPriority::Medium->value, 'sort_order' => 1]);

    $project->forceDelete();

    expect(Task::withTrashed()->count())->toBe(0);
});

test('implementation notes are force deleted when task is force deleted', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Task',
        'description' => 'Has notes.',
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $task->implementationNotes()->create(['title' => 'Note', 'content' => 'Content.']);
    $task->forceDelete();

    expect(ImplementationNote::withTrashed()->count())->toBe(0);
});

test('subtasks are force deleted when parent task is force deleted', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $parent = $project->tasks()->create([
        'title' => 'Parent',
        'description' => 'Has subtasks.',
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $subtask = $project->tasks()->create([
        'title' => 'Subtask',
        'description' => 'Child.',
        'parent_task_id' => $parent->id,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $parent->forceDelete();

    expect(Task::withTrashed()->where('id', $subtask->id)->exists())->toBeFalse()
        ->and(Task::withTrashed()->where('id', $parent->id)->exists())->toBeFalse();
});

test('subtasks and notes are force deleted on deep cascade via project', function (): void {
    $project = Project::factory()->create(['name' => 'Test Project']);
    $parent = $project->tasks()->create([
        'title' => 'Parent',
        'description' => 'Has subtasks.',
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $project->tasks()->create([
        'title' => 'Subtask',
        'description' => 'Child.',
        'parent_task_id' => $parent->id,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $parent->implementationNotes()->create(['title' => 'Note', 'content' => 'Content.']);

    $project->forceDelete();

    expect(Task::withTrashed()->count())->toBe(0)
        ->and(ImplementationNote::withTrashed()->count())->toBe(0);
});
