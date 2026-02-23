<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Task;

use App\Actions\Tasks\DeleteTask;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    /**
     * Delete a task.
     */
    public function __invoke(Request $request, Project $project, Task $task, DeleteTask $deleteTask): RedirectResponse
    {
        $this->authorize('view', $project);

        $deleteTask($task);

        return to_route('projects.tasks.index', $project);
    }
}
