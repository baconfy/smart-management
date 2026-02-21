<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Conversation;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShowController extends Controller
{
    /**
     * Displays the details of a specific conversation within a project.
     *
     * @param  Project  $project  The project to which the conversation belongs.
     * @param  Conversation  $conversation  The conversation instance being displayed.
     *
     * @throws AuthorizationException If the user is not authorized to view the project.
     * @throws HttpException If the conversation does not belong to the specified project.
     */
    public function __invoke(Project $project, Conversation $conversation): Response
    {
        $this->authorize('view', $project);

        abort_unless($conversation->project_id === $project->id, 404);

        return Inertia::render('projects/conversations/show', [
            'project' => $project->only('id', 'ulid', 'name', 'description', 'created_at'),
            'agents' => $project->agents()->orderBy('name')->get(),
            'conversations' => $project->conversations()->select('id', 'title', 'updated_at')->latest('updated_at')->cursorPaginate(20),
            'conversation' => $conversation->only('id', 'title', 'created_at', 'updated_at'),
            'messages' => $conversation->messages()->orderBy('created_at')->get(),
        ]);
    }
}
