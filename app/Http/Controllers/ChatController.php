<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Stores\ProjectConversationStore;
use App\Http\Requests\StoreChatMessageRequest;
use App\Jobs\GenerateConversationTitle;
use App\Jobs\ProcessChatMessage;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Handle the incoming request to store a chat message for a project conversation.
     *
     * This method authorizes the access to the given project, associates the project
     * with the conversation store, validates and extracts the message from the request,
     * and resolves the conversation. It then stores the raw user message and dispatches
     * a job to process the chat message.
     *
     * @param  StoreChatMessageRequest  $request  The request instance containing validated data.
     * @param  Project  $project  The project associated with the conversation.
     * @param  ProjectConversationStore  $store  The conversation store responsible for handling project-specific operations.
     * @return RedirectResponse A redirect response to the project's conversation page.
     */
    public function __invoke(StoreChatMessageRequest $request, Project $project, ProjectConversationStore $store): RedirectResponse
    {
        $this->authorize('view', $project);

        $store->forProject($project);
        $message = $request->validated('message');
        $conversation = $this->resolveConversation($request, $store, $message);
        $store->storeRawUserMessage($conversation->id, $request->user()->id, $message);

        ProcessChatMessage::dispatch($conversation, $project, $message, $request->validated('agent_ids', []));

        return to_route('projects.conversations.show', [$project, $conversation->id]);
    }

    /**
     * Resolves a conversation based on the provided request. If an existing
     * conversation ID is included in the request, retrieves the corresponding
     * conversation. Otherwise, creates a new conversation using the provided
     * store, truncating the message to 100 characters with word preservation.
     *
     * Dispatches a job to generate a title for a newly created conversation.
     *
     * @param  StoreChatMessageRequest  $request  The request containing conversation parameters.
     * @param  ProjectConversationStore  $store  The service responsible for conversation creation.
     * @param  string  $message  The message content to include in the conversation.
     * @return Conversation The resolved or newly created conversation model.
     *
     * @throws ModelNotFoundException If the conversation ID provided in the request is invalid.
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
}
