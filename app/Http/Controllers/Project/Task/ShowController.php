<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Task;

use App\Enums\AgentType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowController extends Controller
{
    /**
     * Displays the details of a task within a project.
     *
     * @throws AuthorizationException If the user is unauthorized to view the project.
     * @throws NotFoundHttpException If the task does not belong to the specified project.
     */
    public function __invoke(Request $request, Project $project, Task $task): Response
    {
        $this->authorize('view', $project);

        abort_unless($task->project_id === $project->id, 404);

        $conversation = $task->conversation;
        $technicalAgent = $project->agents()->where('type', AgentType::Technical)->first();

        return Inertia::render('projects/tasks/show', [
            'project' => $project,
            'agents' => $project->agents()->orderBy('name')->get(),
            'task' => $task->load('status'),
            'subtasks' => $task->subtasks()->with('status')->get(),
            'implementationNotes' => $task->implementationNotes()->latest()->get(),
            'conversation' => $conversation,
            'messages' => $conversation ? $conversation->messages()->whereNull('meta->hidden')->oldest()->get() : [],
            'defaultAgentIds' => $technicalAgent ? [$technicalAgent->id] : [],
        ]);
    }
}
