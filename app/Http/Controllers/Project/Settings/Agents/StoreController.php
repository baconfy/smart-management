<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Settings\Agents;

use App\Actions\ProjectAgents\CreateProjectAgent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class StoreController extends Controller
{
    /**
     * Handles the invocation of the store agent request for a specific project.
     */
    public function __invoke(StoreAgentRequest $request, Project $project, CreateProjectAgent $createProjectAgent): RedirectResponse
    {
        $this->authorize('view', $project);

        ($createProjectAgent)($project, $request->validated());

        return back();
    }
}
