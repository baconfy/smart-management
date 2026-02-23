<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Task;

use App\Actions\Tasks\UpdateTask;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RenameController extends Controller
{
    /**
     * Rename a task.
     */
    public function __invoke(Request $request, Project $project, Task $task, UpdateTask $updateTask): RedirectResponse
    {
        $this->authorize('view', $project);

        $updateTask($task, $request->validate(['title' => ['required', 'string', 'max:255']]));

        return back();
    }
}
