<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ConversationMessages\CreateConversationMessage;
use App\Ai\Agents\GenericAgent;
use App\Events\AgentMessageReceived;
use App\Events\AgentProcessingFailed;
use App\Models\Conversation;
use App\Models\ProjectAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessAgentMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 5;

    /**
     * Initialize the job with conversation, agent, and message.
     */
    public function __construct(public readonly Conversation $conversation, public readonly ProjectAgent $projectAgent, public readonly string $message) {}

    /**
     * Execute the agent prompt, store the response, and broadcast the event.
     */
    public function handle(CreateConversationMessage $createConversationMessage): void
    {
        try {
            $agent = GenericAgent::make(projectAgent: $this->projectAgent);
            $agent->withConversationHistory($this->conversation->id);

            $response = $agent->prompt($this->message, model: $this->projectAgent->model);

            $savedMessage = $createConversationMessage($this->conversation, [
                'id' => (string) Str::ulid(),
                'user_id' => $this->conversation->user_id,
                'project_agent_id' => $this->projectAgent->id,
                'agent' => $this->projectAgent->name,
                'role' => 'assistant',
                'content' => $response->text,
            ]);

            AgentMessageReceived::dispatch($savedMessage);
        } catch (\Throwable $e) {
            Log::error('ProcessAgentMessage attempt failed', [
                'conversation_id' => $this->conversation->id,
                'agent_id' => $this->projectAgent->id,
                'agent_name' => $this->projectAgent->name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        AgentProcessingFailed::dispatch(
            $this->conversation,
            $this->projectAgent->id,
            $this->projectAgent->name,
            'The agent failed to respond. Please try again.',
        );

        Log::error('ProcessAgentMessage failed permanently', [
            'conversation_id' => $this->conversation->id,
            'agent_id' => $this->projectAgent->id,
            'agent_name' => $this->projectAgent->name,
            'error' => $exception->getMessage(),
        ]);
    }
}
