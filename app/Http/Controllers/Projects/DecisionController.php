<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DecisionController extends Controller
{
    /**
     * Display a listing of decisions related to the specified project.
     *
     * @param  Request  $request  The current HTTP request instance.
     * @param  Project  $project  The project instance for which decisions are being retrieved.
     * @return Response The rendered view containing the project and its associated decisions.
     */
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/decisions/index', [
            'project' => $project,
            'decisions' => $project->decisions()->latest()->get(),
        ]);
    }
}
