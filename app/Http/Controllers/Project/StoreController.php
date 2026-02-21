<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Services\CreateProjectService;
use Illuminate\Http\RedirectResponse;

class StoreController extends Controller
{
    /**
     * Store a newly created project.
     */
    public function __invoke(StoreProjectRequest $request, CreateProjectService $service): RedirectResponse
    {
        $project = $service($request->user(), $request->validated());

        return redirect()->to(route('projects.show', $project));
    }
}
