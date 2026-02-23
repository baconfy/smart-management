<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Settings;

use App\Actions\Projects\DeleteProject;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    /**
     * Delete the project permanently.
     */
    public function __invoke(Request $request, Project $project, DeleteProject $deleteProject): RedirectResponse
    {
        $this->authorize('view', $project);

        $deleteProject($project);

        return to_route('projects.index');
    }
}
