<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Settings;

use App\Actions\Projects\UpdateProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class UpdateController extends Controller
{
    /**
     * Update the project settings.
     */
    public function __invoke(UpdateProjectRequest $request, Project $project, UpdateProject $updateProject): RedirectResponse
    {
        $this->authorize('view', $project);

        $updateProject($project, $request->validated());

        return back();
    }
}
