<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    /**
     * Display a listing of the user's projects.
     */
    public function __invoke(Request $request): Response
    {
        $projects = $request->user()->projects()->latest('projects.created_at')->get([
            'projects.id', 'projects.ulid', 'projects.name', 'projects.description', 'projects.created_at',
        ]);

        return Inertia::render('projects/index', ['projects' => $projects]);
    }
}
