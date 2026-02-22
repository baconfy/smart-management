<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Settings\Agents;

use App\Actions\ProjectAgents\DeleteProjectAgent;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    /**
     * Handles to delete of a specific agent belonging to the given project.
     */
    public function __invoke(Request $request, Project $project, ProjectAgent $agent, DeleteProjectAgent $deleteProjectAgent): RedirectResponse
    {
        $this->authorize('view', $project);

        $deleteProjectAgent($agent);

        return to_route('projects.agents.index', $project)->with('success', 'Agent deleted successfully.');
    }
}
