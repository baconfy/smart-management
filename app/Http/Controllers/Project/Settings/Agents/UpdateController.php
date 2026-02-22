<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Settings\Agents;

use App\Actions\ProjectAgents\UpdateProjectAgent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentRequest;
use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Http\RedirectResponse;

class UpdateController extends Controller
{
    /**
     * Handles the update of a specific agent belonging to the given project.
     */
    public function __invoke(StoreAgentRequest $request, Project $project, ProjectAgent $agent, UpdateProjectAgent $updateProjectAgent): RedirectResponse
    {
        $this->authorize('view', $project);

        $updateProjectAgent($agent, $request->validated());

        return back();
    }
}
