<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelectAgentsRequest;
use App\Models\Conversation;
use App\Models\Project;
use App\Services\DispatchAgentsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class SelectAgentsController extends Controller
{
    /**
     * Handles the storage process for agents in a given project and conversation.
     *
     * @throws AuthorizationException If the authenticated user is not authorized to view the project.
     */
    public function __invoke(SelectAgentsRequest $request, Project $project, Conversation $conversation, DispatchAgentsService $dispatchAgents): JsonResponse
    {
        $this->authorize('view', $project);

        $agents = $project->agents()->whereIn('id', $request->validated('agent_ids'))->get();

        $dispatchAgents($conversation, $agents, $request->validated('message'));

        return response()->json(['status' => 'ok']);
    }
}
