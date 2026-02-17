<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\ArchitectAgent;
use App\Ai\Agents\GenericAgent;
use App\Ai\Stores\ProjectConversationStore;
use App\Enums\AgentType;
use App\Http\Requests\StoreChatMessageRequest;
use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Agent;

class ChatController extends Controller
{
    use AuthorizesRequests;

    /**
     * Send a message to one or more agents.
     *
     * Conversation lifecycle is managed manually to support multi-agent
     * scenarios without duplicating user messages.
     *
     * TODO: Step 11b — switch from prompt() to broadcastNow() via Reverb.
     */
    public function store(StoreChatMessageRequest $request, Project $project, ProjectConversationStore $store): JsonResponse
    {
        $this->authorize('view', $project);

        $user = $request->user();
        $message = $request->validated('message');
        $agentIds = $request->validated('agent_ids');
        $conversationId = $request->validated('conversation_id');

        // Configure store with project context
        $store->forProject($project);

        // Create conversation if new
        if (! $conversationId) {
            $conversationId = $store->storeConversation($user->id, Str::limit($message, 100, preserveWords: true));
        }

        // Store user message at once (no duplicates)
        $store->storeRawUserMessage($conversationId, $user->id, $message);

        // Prompt each selected agent
        $projectAgents = $project->agents()->whereIn('id', $agentIds)->get();

        foreach ($projectAgents as $projectAgent) {
            $agent = $this->resolveAgent($projectAgent);

            // Read conversation history without triggering SDK middleware
            $agent->withConversationHistory($conversationId);

            $response = $agent->prompt($message);

            // Store assistant response manually
            $store->storeRawAssistantMessage($conversationId, $user->id, $projectAgent->id, $agent::class, $response->text);
        }

        return response()->json(['conversation_id' => $conversationId]);
    }

    /**
     * Resolve the agent class from a ProjectAgent model.
     */
    private function resolveAgent(ProjectAgent $projectAgent): Agent
    {
        return match ($projectAgent->type) {
            AgentType::Architect => ArchitectAgent::make(projectAgent: $projectAgent),
            default => GenericAgent::make(projectAgent: $projectAgent),
        };
    }
}
