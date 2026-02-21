<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Task;

use App\Actions\Tasks\UpdateTask;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    /**
     * Update a task's status or sort order (kanban drag and drop).
     */
    public function __invoke(Request $request, Project $project, Task $task, UpdateTask $updateTask): RedirectResponse
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
}
