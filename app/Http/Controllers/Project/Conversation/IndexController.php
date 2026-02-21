<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Conversation;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    /**
     * Display the conversations index for a specified project.
     */
    public function __invoke(Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/conversations/index', [
            'project' => $project->only('id', 'ulid', 'name', 'description', 'created_at'),
            'agents' => $project->agents()->orderBy('name')->get(),
            'conversations' => $project->conversations()->select('id', 'title', 'updated_at')->latest('updated_at')->cursorPaginate(20),
        ]);
    }
}
