<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\ImplementationNote;
use App\Models\Project;
use App\Models\Task;

// ============================================================================
// Task Creation
// ============================================================================

test('can create a task with required fields', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Implement HD Wallet Derivation',
        'description' => 'Create BIP44 derivation path for deposit addresses.',
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    expect($task)
        ->toBeInstanceOf(Task::class)
        ->title->toBe('Implement HD Wallet Derivation')
        ->status->toBe(TaskStatus::Backlog)
        ->priority->toBe(TaskPriority::High)
        ->sort_order->toBe(1);
});

test('task has nullable optional fields', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Simple task',
        'description' => 'No extras.',
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    expect($task)
        ->phase->toBeNull()
        ->milestone->toBeNull()
        ->estimate->toBeNull()
        ->parent_task_id->toBeNull()
        ->conversation_message_id->toBeNull();
});

test('task stores phase and milestone', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Scanner implementation',
        'description' => 'Build blockchain scanner.',
        'phase' => 'Phase 2',
        'milestone' => 'Core Infrastructure',
        'estimate' => '8h',
        'status' => TaskStatus::Backlog->value,
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
    $project = Project::create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Test task',
        'description' => 'A task.',
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    expect($task->project)
        ->toBeInstanceOf(Project::class)
        ->id->toBe($project->id);
});

test('project has many tasks', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $project->tasks()->create([
        'title' => 'Task A',
        'description' => 'First.',
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $project->tasks()->create([
        'title' => 'Task B',
        'description' => 'Second.',
        'status' => TaskStatus::InProgress->value,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 2,
    ]);

    expect($project->tasks)->toHaveCount(2);
});

// ============================================================================
// Subtasks
// ============================================================================

test('task can have subtasks', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $parent = $project->tasks()->create([
        'title' => 'Parent task',
        'description' => 'Has children.',
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $project->tasks()->create([
        'title' => 'Subtask A',
        'description' => 'Child A.',
        'parent_task_id' => $parent->id,
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $project->tasks()->create([
        'title' => 'Subtask B',
        'description' => 'Child B.',
        'parent_task_id' => $parent->id,
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::Low->value,
        'sort_order' => 2,
    ]);

    expect($parent->subtasks)->toHaveCount(2);
});

test('subtask belongs to parent', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $parent = $project->tasks()->create([
        'title' => 'Parent',
        'description' => 'Parent task.',
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $child = $project->tasks()->create([
        'title' => 'Child',
        'description' => 'Child task.',
        'parent_task_id' => $parent->id,
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    expect($child->parent)
        ->toBeInstanceOf(Task::class)
        ->id->toBe($parent->id);
});

// ============================================================================
// Task Scopes
// ============================================================================

test('status scope filters tasks', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $project->tasks()->create([
        'title' => 'Todo',
        'description' => 'Not started.',
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $project->tasks()->create([
        'title' => 'Doing',
        'description' => 'In progress.',
        'status' => TaskStatus::InProgress->value,
        'priority' => TaskPriority::High->value,
        'sort_order' => 2,
    ]);

    $project->tasks()->create([
        'title' => 'Finished',
        'description' => 'Complete.',
        'status' => TaskStatus::Done->value,
        'priority' => TaskPriority::Low->value,
        'sort_order' => 3,
    ]);

    expect($project->tasks()->withStatus(TaskStatus::Backlog)->get())->toHaveCount(1);
    expect($project->tasks()->withStatus(TaskStatus::InProgress)->get())->toHaveCount(1);
});

// ============================================================================
// Task Enums
// ============================================================================

test('all task statuses are valid', function (): void {
    $expected = ['backlog', 'in_progress', 'done', 'blocked'];

    $values = array_map(fn (TaskStatus $s) => $s->value, TaskStatus::cases());

    expect($values)->toBe($expected);
});

test('all task priorities are valid', function (): void {
    $expected = ['high', 'medium', 'low'];

    $values = array_map(fn (TaskPriority $p) => $p->value, TaskPriority::cases());

    expect($values)->toBe($expected);
});

// ============================================================================
// Implementation Notes
// ============================================================================

test('can create an implementation note for a task', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'HD Wallet',
        'description' => 'Implement derivation.',
        'status' => TaskStatus::InProgress->value,
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
    $project = Project::create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Scanner',
        'description' => 'Build scanner.',
        'status' => TaskStatus::InProgress->value,
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
    $project = Project::create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Test task',
        'description' => 'A task.',
        'status' => TaskStatus::Backlog->value,
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
    $project = Project::create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Complex task',
        'description' => 'Needs multiple notes.',
        'status' => TaskStatus::InProgress->value,
        'priority' => TaskPriority::High->value,
        'sort_order' => 1,
    ]);

    $task->implementationNotes()->create(['title' => 'Note 1', 'content' => 'First attempt.']);
    $task->implementationNotes()->create(['title' => 'Note 2', 'content' => 'Second approach.']);

    expect($task->implementationNotes)->toHaveCount(2);
});

// ============================================================================
// Cascade Delete
// ============================================================================

test('tasks are deleted when project is deleted', function (): void {
    $project = Project::create(['name' => 'Test Project']);

    $project->tasks()->create([
        'title' => 'Task',
        'description' => 'Will be deleted.',
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $project->delete();

    expect(Task::count())->toBe(0);
});

test('implementation notes are deleted when task is deleted', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Task',
        'description' => 'Has notes.',
        'status' => TaskStatus::Backlog->value,
        'priority' => TaskPriority::Medium->value,
        'sort_order' => 1,
    ]);

    $task->implementationNotes()->create(['title' => 'Note', 'content' => 'Content.']);
    $task->delete();

    expect($task->refresh()->implementationNotes()->count())->toBe(0);
});
