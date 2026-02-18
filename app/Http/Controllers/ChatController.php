<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\ModeratorAgent;
use App\Ai\Stores\ProjectConversationStore;
use App\Events\AgentsProcessing;
use App\Http\Requests\StoreChatMessageRequest;
use App\Jobs\GenerateConversationTitle;
use App\Jobs\ProcessAgentMessage;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Handles the incoming request to store a user's chat message within a project conversation.
     *
     * Authorizes the user's access to the project and processes the validated chat message.
     * Resolves the appropriate conversation and persists the raw message data.
     * Routes the conversation via a moderator if no agent IDs are provided, or dispatches
     * agent-specific jobs for further handling.
     *
     * @param  StoreChatMessageRequest  $request  The HTTP request containing message data and optional agent IDs.
     * @param  Project  $project  The project instance to which the conversation belongs.
     * @param  ProjectConversationStore  $store  Service class handling conversation storage operations.
     * @return RedirectResponse A redirection response to the project's conversation view.
     */
    public function __invoke(StoreChatMessageRequest $request, Project $project, ProjectConversationStore $store): RedirectResponse
    {
        $this->authorize('view', $project);

        $store->forProject($project);
        $message = $request->validated('message');
        $conversation = $this->resolveConversation($request, $store, $message);
        $store->storeRawUserMessage($conversation->id, $request->user()->id, $message);
        $agentIds = $request->validated('agent_ids', []);

        if (empty($agentIds)) {
            $this->routeViaModerator($project, $conversation, $message);
        } else {
            $this->dispatchAgentJobs($project, $conversation, $agentIds, $message);
        }

        return to_route('projects.conversations.show', [$project, $conversation->id]);
    }

    /**
     * Resolves a conversation based on the provided request and message.
     *
     * If a conversation ID is found in the validated request data, it attempts to retrieve
     * the existing conversation from the database. If no conversation ID is provided,
     * a new conversation is created using the given store and message, and a title
     * generation job is dispatched for the newly created conversation.
     *
     * @param  StoreChatMessageRequest  $request  The incoming request containing conversation data.
     * @param  ProjectConversationStore  $store  The service responsible for conversation creation.
     * @param  string  $message  The message used for conversation creation and title generation.
     * @return Conversation The resolved or newly created conversation instance.
     *
     * @throws ModelNotFoundException If the provided conversation ID does not correspond to an existing conversation.
     */
    private function resolveConversation(StoreChatMessageRequest $request, ProjectConversationStore $store, string $message): Conversation
    {
        $conversationId = $request->validated('conversation_id');
        if ($conversationId) {
            return Conversation::findOrFail($conversationId);
        }

        $conversation = $store->createConversation($request->user()->id, Str::limit($message, 100, preserveWords: true));

        GenerateConversationTitle::dispatch($conversation);

        return $conversation;
    }

    /**
     * Dispatches jobs to process messages for specified agents within a project.
     *
     * Retrieves the agents associated with the given project and filters them by the specified agent IDs.
     * A broadcast is sent to indicate processing for the conversation and agents, and individual
     * agent processing jobs are dispatched for each agent.
     *
     * @param  Project  $project  The project containing the agents.
     * @param  Conversation  $conversation  The conversation context for the agent jobs.
     * @param  array  $agentIds  The list of agent IDs to process.
     * @param  string  $message  The message to be processed for each agent.
     */
    private function dispatchAgentJobs(Project $project, Conversation $conversation, array $agentIds, string $message): void
    {
        $agents = $project->agents()->whereIn('id', $agentIds)->get();

        $this->broadcastProcessing($conversation, $agents);

        $agents->each(
            fn ($agent) => ProcessAgentMessage::dispatch($conversation, $agent, $message),
        );
    }

    /**
     * Routes a conversation message through the moderator agent and processes it accordingly.
     *
     * This method uses the moderator agent to determine the appropriate agent types for
     * handling the provided message within the context of a project. Agents with high
     * confidence levels are prioritized, but if no high-confidence results are found,
     * the agent with the highest confidence is selected. The processing is then broadcasted
     * and dispatched for each relevant agent.
     *
     * @param  Project  $project  The project within which the conversation and agents operate.
     * @param  Conversation  $conversation  The conversation to which the message belongs.
     * @param  string  $message  The message to be routed and processed by the agents.
     */
    private function routeViaModerator(Project $project, Conversation $conversation, string $message): void
    {
        $moderator = new ModeratorAgent($project);
        $result = $moderator->route($message);

        $highConfidence = $moderator->highConfidenceAgents($result);
        $types = ! empty($highConfidence)
            ? collect($highConfidence)->pluck('type')->toArray()
            : [collect($result['agents'])->sortByDesc('confidence')->first()['type']];

        $agents = $project->agents()->whereIn('type', $types)->get();

        $this->broadcastProcessing($conversation, $agents);

        $agents->each(
            fn ($agent) => ProcessAgentMessage::dispatch($conversation, $agent, $message),
        );
    }

    private function broadcastProcessing(Conversation $conversation, $agents): void
    {
        AgentsProcessing::dispatch($conversation, $agents->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values()->toArray());
    }
}
