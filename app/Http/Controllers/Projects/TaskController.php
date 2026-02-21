<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Tasks\UpdateTask;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskController extends Controller
{
    /**
     * Display the kanban board for the specified project.
     */
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/tasks/index', [
            'project' => $project,
            'statuses' => $project->statuses()->ordered()->get(),
            'tasks' => $project->tasks()->with('status')->whereNull('parent_task_id')->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Update a task's status or sort order (kanban drag and drop).
     */
    public function update(Request $request, Project $project, Task $task, UpdateTask $updateTask): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('view', $project);

        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'task_status_id' => ['sometimes', 'exists:task_statuses,id'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $updateTask($task, $validated);

        return back();
    }

    /**
     * Displays the details of a task within a project.
     *
     * @throws AuthorizationException If the user is unauthorized to view the project.
     * @throws NotFoundHttpException If the task does not belong to the specified project.
     */
    public function show(Request $request, Project $project, Task $task): Response
    {
        $this->authorize('view', $project);

        abort_unless($task->project_id === $project->id, 404);

        $conversation = $task->conversation;

        return Inertia::render('projects/tasks/show', [
            'project' => $project,
            'task' => $task->load('status'),
            'subtasks' => $task->subtasks()->with('status')->get(),
            'implementationNotes' => $task->implementationNotes()->latest()->get(),
            'conversation' => $conversation,
            'messages' => $conversation ? $conversation->messages()->oldest()->get() : [],
        ]);
    }
}
