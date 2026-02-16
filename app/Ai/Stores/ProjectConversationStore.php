<?php

declare(strict_types=1);

namespace App\Ai\Stores;

use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Storage\DatabaseConversationStore;

class ProjectConversationStore extends DatabaseConversationStore implements ConversationStore
{
    protected ?int $projectId = null;

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
        $query = DB::table('agent_conversations')
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc');

        if ($this->projectId !== null) {
            $query->where('project_id', $this->projectId);
        }

        return $query->first()?->id;
    }

    /**
     * Store a new conversation with the project context.
     */
    public function storeConversation(string|int|null $userId, string $title): string
    {
        $conversationId = (string) Str::uuid7();

        DB::table('agent_conversations')->insert([
            'id' => $conversationId,
            'user_id' => $userId,
            'project_id' => $this->projectId,
            'title' => $title,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $conversationId;
    }

    /**
     * Store a user message (no agent context).
     */
    public function storeUserMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt): string
    {
        $messageId = (string) Str::uuid7();

        DB::table('agent_conversation_messages')->insert([
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'project_agent_id' => null,
            'agent' => $prompt->agent::class,
            'role' => 'user',
            'content' => $prompt->prompt,
            'attachments' => $prompt->attachments->toJson(),
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '[]',
            'meta' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $messageId;
    }

    /**
     * Store an assistant message with agent context.
     */
    public function storeAssistantMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt, AgentResponse $response): string
    {
        $messageId = (string) Str::uuid7();

        DB::table('agent_conversation_messages')->insert([
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'project_agent_id' => $this->projectAgentId,
            'agent' => $prompt->agent::class,
            'role' => 'assistant',
            'content' => $response->text,
            'attachments' => '[]',
            'tool_calls' => json_encode($response->toolCalls),
            'tool_results' => json_encode($response->toolResults),
            'usage' => json_encode($response->usage),
            'meta' => json_encode($response->meta),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $messageId;
    }
}
