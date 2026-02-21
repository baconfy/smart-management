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

        return Inertia::render('projects/show', [
            'project' => $project->only('id', 'ulid', 'name', 'description', 'created_at'),
        ]);
    }
}
