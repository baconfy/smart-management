<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\BusinessRule;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    /**
     * Handle the display of the business rules index page for a specific project.
     */
    public function __invoke(Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/business-rules/index', [
            'project' => $project,
            'businessRules' => $project->businessRules()->latest()->get(),
        ]);
    }
}
