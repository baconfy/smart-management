<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Task;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    /**
     * Display the kanban board for the specified project.
     */
    public function __invoke(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/tasks/index', [
            'project' => $project,
            'statuses' => $project->statuses()->ordered()->get(),
            'tasks' => $project->tasks()->with('status')->whereNull('parent_task_id')->orderBy('sort_order')->get(),
        ]);
    }
}
