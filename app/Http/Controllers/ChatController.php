<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Stores\ProjectConversationStore;
use App\Http\Requests\StoreChatMessageRequest;
use App\Jobs\GenerateConversationTitle;
use App\Jobs\ProcessAgentMessage;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Handles the storing of a chat message within a project conversation.
     *
     * @param  StoreChatMessageRequest  $request  The request instance containing validated data.
     * @param  Project  $project  The project entity associated with the conversation.
     * @param  ProjectConversationStore  $store  The service responsible for conversation data persistence.
     * @return RedirectResponse Redirects to the show conversation route of the specified project.
     *
     * @throws AuthorizationException If the user is not authorized to view the project.
     */
    public function __invoke(StoreChatMessageRequest $request, Project $project, ProjectConversationStore $store): RedirectResponse
    {
        $this->authorize('view', $project);

        $store->forProject($project);

        $message = $request->validated('message');

        $conversation = $this->resolveConversation($request, $store, $message);
        $store->storeRawUserMessage($conversation->id, $request->user()->id, $message);
        $this->dispatchAgentJobs($project, $conversation, $request->validated('agent_ids', []), $message);

        return to_route('projects.conversations.show', [$project, $conversation->id]);
    }

    /**
     * Resolves the conversation with a chat message.
     *
     * This method retrieves an existing conversation if the `conversation_id` is present
     * in the validated request. Otherwise, it creates a new conversation using the
     * provided conversation store and dispatches a job to generate the conversation title.
     *
     * @param  StoreChatMessageRequest  $request  The request instance containing validated data.
     * @param  ProjectConversationStore  $store  The service responsible for creating conversations.
     * @param  string  $message  The chat message to be associated with the conversation.
     * @return Conversation The resolved or newly created conversation instance.
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
     * Dispatches jobs to process messages for multiple agents.
     *
     * This method iterates through the specified agent IDs associated with a project
     * and dispatches a job for each agent to handle the provided message within the
     * context of the given conversation.
     *
     * @param  Project  $project  The project instance containing the agents.
     * @param  Conversation  $conversation  The conversation for which the message is being processed.
     * @param  array  $agentIds  The list of agent IDs to process the message for.
     * @param  string  $message  The message to be processed by each agent.
     */
    private function dispatchAgentJobs(Project $project, Conversation $conversation, array $agentIds, string $message): void
    {
        $project->agents()->whereIn('id', $agentIds)->each(
            fn ($agent) => ProcessAgentMessage::dispatch($conversation, $agent, $message),
        );
    }
}
