<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends Controller
{
    /**
     * Display the specified project.
     */
    public function __invoke(Project $project): Response
    {
        $this->authorize('view', $project);

        $project->loadCount([
            'tasks',
            'tasks as tasks_open_count' => fn ($q) => $q->open(),
            'tasks as tasks_closed_count' => fn ($q) => $q->closed(),
            'decisions as decisions_count' => fn ($q) => $q->active(),
            'businessRules as business_rules_count' => fn ($q) => $q->active(),
            'conversations',
        ]);

        $project->load([
            'tasks' => fn ($q) => $q->with('status')->latest('updated_at')->limit(5),
            'decisions' => fn ($q) => $q->active()->latest()->limit(5),
        ]);

        return Inertia::render('projects/show', [
            'project' => $project,
        ]);
    }
}
