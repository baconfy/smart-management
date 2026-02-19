<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

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
     * Display a listing of tasks for the specified project.
     *
     * @param  Request  $request  The incoming HTTP request instance.
     * @param  Project  $project  The project instance for which tasks are being listed.
     * @return Response The response containing the rendered tasks view.
     */
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/tasks/index', [
            'project' => $project,
            'tasks' => $project->tasks()->whereNull('parent_task_id')->latest()->get(),
        ]);
    }

    /**
     * Displays the details of a task within a project.
     *
     * @param  Request  $request  The incoming HTTP request instance.
     * @param  Project  $project  The project instance being accessed.
     * @param  Task  $task  The task instance belonging to the project.
     * @return Response The rendered response containing project, task, and related data.
     *
     * @throws AuthorizationException If the user is unauthorized to view the project.
     * @throws NotFoundHttpException If the task does not belong to the specified project.
     */
    public function show(Request $request, Project $project, Task $task): Response
    {
        $this->authorize('view', $project);

        abort_unless($task->project_id === $project->id, 404);

        return Inertia::render('projects/tasks/show', [
            'project' => $project,
            'task' => $task,
            'subtasks' => $task->subtasks()->get(),
            'implementationNotes' => $task->implementationNotes()->latest()->get(),
        ]);
    }
}
