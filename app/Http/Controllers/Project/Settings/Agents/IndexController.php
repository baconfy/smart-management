<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Settings\Agents;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    /**
     * Display a listing of the user's projects.
     */
    public function __invoke(Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/settings/agents/index', [
            'project' => $project,
            'agents' => $project->agents()->orderBy('name')->get(),
        ]);
    }
}
