<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConversationController extends Controller
{
    /**
     * Handle the request to display the conversations index for a specified project.
     *
     * This method authorizes the user to view the specified project and renders
     * the conversations index page using the Inertia framework. The rendered page
     * includes project details and a list of agents associated with the project,
     * ordered alphabetically by name.
     *
     * @param  Project  $project  The project instance being accessed.
     * @return Response The rendered Inertia response for the conversations index page.
     */
    public function index(Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/conversations/index', [
            'project' => $project->only('id', 'ulid', 'name', 'description', 'created_at'),
            'agents' => $project->agents()->orderBy('name')->get(),
        ]);
    }

    /**
     * Displays the details of a specific conversation within a project.
     *
     * @param  Project  $project  The project to which the conversation belongs.
     * @param  Conversation  $conversation  The conversation instance being displayed.
     *
     * @throws AuthorizationException If the user is not authorized to view the project.
     * @throws HttpException If the conversation does not belong to the specified project.
     */
    public function show(Project $project, Conversation $conversation): Response
    {
        $this->authorize('view', $project);

        abort_unless($conversation->project_id === $project->id, 404);

        return Inertia::render('projects/conversations/show', [
            'project' => $project->only('id', 'ulid', 'name', 'description', 'created_at'),
            'agents' => $project->agents()->orderBy('name')->get(),
            'conversation' => $conversation->only('id', 'title', 'created_at', 'updated_at'),
            'messages' => $conversation->messages()->orderBy('created_at')->get(),
        ]);
    }
}
