<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Settings;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  Project  $project  The project instance to process.
     * @return Response The HTTP response instance.
     */
    public function __invoke(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/settings/index', ['project' => $project]);
    }
}
