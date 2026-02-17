<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Services\CreateProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the user's projects.
     */
    public function index(Request $request): Response
    {
        $projects = $request->user()->projects()->latest('projects.created_at')->get([
            'projects.id', 'projects.ulid', 'projects.name', 'projects.description', 'projects.created_at',
        ]);

        return Inertia::render('projects/index', ['projects' => $projects]);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request, CreateProjectService $service): RedirectResponse
    {
        $project = $service($request->user(), $request->validated());

        return redirect()->to(route('projects.show', $project));
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/show', [
            'project' => $project->only('id', 'ulid', 'name', 'description', 'created_at'),
        ]);
    }
}
