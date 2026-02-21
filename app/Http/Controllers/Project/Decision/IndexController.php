<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Decision;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    /**
     * Display a listing of decisions related to the specified project.
     */
    public function __invoke(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/decisions/index', [
            'project' => $project,
            'decisions' => $project->decisions()->latest()->get(),
        ]);
    }
}
