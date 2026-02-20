<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class BusinessRuleController extends Controller
{
    /**
     * Handle the display of the business rules index page for a specific project.
     *
     * @param  Project  $project  The project instance being viewed.
     * @return Response The HTTP response containing the rendered business rules index page.
     */
    public function index(Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/business-rules/index', [
            'project' => $project,
            'businessRules' => $project->businessRules()->latest()->get(),
        ]);
    }
}
