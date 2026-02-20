<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\ConversationMessages\CreateConversationMessage;
use App\Actions\Conversations\CreateConversation;
use App\Jobs\GenerateConversationTitle;
use App\Jobs\ProcessChatMessage;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

readonly class SendChatMessageService
{
    /**
     * Initialize the service with its action dependencies.
     */
    public function __construct(private CreateConversation $createConversation, private CreateConversationMessage $createConversationMessage) {}

    /**
     * Send a chat message within a project conversation.
     *
     * Resolves or creates the conversation, stores the user message,
     * and dispatches the processing job.
     *
     * @param  array<int>  $agentIds
     */
    public function __invoke(Project $project, User $user, string $message, ?string $conversationId = null, array $agentIds = []): Conversation
    {
        $conversation = $conversationId ? Conversation::findOrFail($conversationId) : $this->createNewConversation($project, $user, $message);

        ($this->createConversationMessage)($conversation, ['id' => (string) Str::ulid(), 'user_id' => $user->id, 'role' => 'user', 'content' => $message]);

        ProcessChatMessage::dispatch($conversation, $project, $message, $agentIds);

        return $conversation;
    }

    /**
     * Create a new conversation and dispatch title generation.
     */
    private function createNewConversation(Project $project, User $user, string $message): Conversation
    {
        $conversation = ($this->createConversation)($project, ['id' => (string) Str::ulid(), 'user_id' => $user->id, 'title' => Str::limit($message, 100, preserveWords: true)]);

        GenerateConversationTitle::dispatch($conversation);

        return $conversation;
    }
}
