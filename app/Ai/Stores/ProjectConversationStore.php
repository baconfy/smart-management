<?php

declare(strict_types=1);

namespace App\Ai\Stores;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Storage\DatabaseConversationStore;

class ProjectConversationStore extends DatabaseConversationStore implements ConversationStore
{
    /**
     * The project ID to scope conversations to.
     */
    protected ?int $projectId = null;

    /**
     * The project agent ID to scope assistant messages to.
     */
    protected ?int $projectAgentId = null;

    /**
     * Set the project context for subsequent operations.
     */
    public function forProject(Project $project): static
    {
        $this->projectId = $project->id;

        return $this;
    }

    /**
     * Set the agent context for subsequent message storage.
     */
    public function withAgent(ProjectAgent $agent): static
    {
        $this->projectAgentId = $agent->id;

        return $this;
    }

    /**
     * Clear project and agent context.
     */
    public function reset(): static
    {
        $this->projectId = null;
        $this->projectAgentId = null;

        return $this;
    }

    /**
     * Get the most recent conversation ID for a given user (scoped by project).
     */
    public function latestConversationId(string|int $userId): ?string
    {
        return Conversation::where('user_id', $userId)->when($this->projectId, fn ($q) => $q->where('project_id', $this->projectId))->latest('updated_at')->value('id');
    }

    /**
     * Store a new conversation with the project context (SDK contract).
     */
    public function storeConversation(string|int|null $userId, string $title): string
    {
        return $this->createConversation($userId, $title)->id;
    }

    /**
     * Create a new conversation and return the model.
     */
    public function createConversation(string|int|null $userId, string $title): Conversation
    {
        return Conversation::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'project_id' => $this->projectId,
            'title' => $title,
        ])->fresh();
    }

    /**
     * Store a user message (SDK-compatible, requires AgentPrompt).
     */
    public function storeUserMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt): string
    {
        return ConversationMessage::create([
            'id' => (string) Str::ulid(),
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'agent' => $prompt->agent::class,
            'role' => 'user',
            'content' => $prompt->prompt,
            'attachments' => $prompt->attachments->toJson(),
        ])->id;
    }

    /**
     * Store an assistant message (SDK-compatible, requires AgentResponse).
     */
    public function storeAssistantMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt, AgentResponse $response): string
    {
        return ConversationMessage::create([
            'id' => (string) Str::ulid(),
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'project_agent_id' => $this->projectAgentId,
            'agent' => $prompt->agent::class,
            'role' => 'assistant',
            'content' => $response->text,
            'tool_calls' => $response->toolCalls,
            'tool_results' => $response->toolResults,
            'usage' => $response->usage,
            'meta' => $response->meta,
        ])->id;
    }

    /**
     * Store a user message without needing an AgentPrompt.
     */
    public function storeRawUserMessage(string $conversationId, string|int $userId, string $content): ConversationMessage
    {
        return ConversationMessage::create([
            'id' => (string) Str::ulid(),
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'role' => 'user',
            'content' => $content,
        ]);
    }

    /**
     * Store an assistant message without needing an AgentResponse.
     */
    public function storeRawAssistantMessage(string $conversationId, string|int $userId, int $projectAgentId, string $agentClass, string $content): ConversationMessage
    {
        return ConversationMessage::create([
            'id' => (string) Str::ulid(),
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'project_agent_id' => $projectAgentId,
            'agent' => $agentClass,
            'role' => 'assistant',
            'content' => $content,
        ]);
    }
}
