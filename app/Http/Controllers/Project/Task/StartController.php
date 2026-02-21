<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Task;

use App\Actions\Tasks\StartTaskConversation;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StartController extends Controller
{
    /**
     * Handles the invocation of a task-related conversation start.
     */
    public function __invoke(Request $request, Project $project, Task $task, StartTaskConversation $startTaskConversation): RedirectResponse
    {
        $this->authorize('view', $project);

        abort_unless($task->project_id === $project->id, 404);

        $startTaskConversation($task, $request->user());

        return back();
    }
}
