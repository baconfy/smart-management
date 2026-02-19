<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Events\AgentsProcessing;
use App\Http\Controllers\Controller;
use App\Http\Requests\SelectAgentsRequest;
use App\Jobs\ProcessAgentMessage;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class SelectAgentsController extends Controller
{
    /**
     * Handles the storage process for agents in a given project and conversation.
     *
     * @param  SelectAgentsRequest  $request  The incoming HTTP request containing validated agent IDs and message data.
     * @param  Project  $project  The project to which the agents belong.
     * @param  Conversation  $conversation  The conversation context for agent processing.
     * @return JsonResponse Returns a JSON response indicating the success status of the operation.
     *
     * @throws AuthorizationException If the authenticated user is not authorized to view the project.
     */
    public function store(SelectAgentsRequest $request, Project $project, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $project);

        $agents = $project->agents()->whereIn('id', $request->validated('agent_ids'))->get();
        $message = $request->validated('message');

        AgentsProcessing::dispatch($conversation, $agents->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values()->toArray());

        $agents->each(fn ($agent) => ProcessAgentMessage::dispatch($conversation, $agent, $message));

        return response()->json(['status' => 'ok']);
    }
}
