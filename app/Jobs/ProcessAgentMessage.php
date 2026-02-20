<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ConversationMessages\CreateConversationMessage;
use App\Ai\Agents\GenericAgent;
use App\Events\AgentMessageReceived;
use App\Models\Conversation;
use App\Models\ProjectAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ProcessAgentMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Initialize the job with conversation, agent, and message.
     */
    public function __construct(public readonly Conversation $conversation, public readonly ProjectAgent $projectAgent, public readonly string $message) {}

    /**
     * Execute the agent prompt, store the response, and broadcast the event.
     */
    public function handle(CreateConversationMessage $createConversationMessage): void
    {
        $agent = GenericAgent::make(projectAgent: $this->projectAgent);
        $agent->withConversationHistory($this->conversation->id);

        $response = $agent->prompt($this->message, model: $this->projectAgent->model);

        $savedMessage = $createConversationMessage($this->conversation, [
            'id' => (string) Str::ulid(),
            'user_id' => $this->conversation->user_id,
            'project_agent_id' => $this->projectAgent->id,
            'agent' => $agent::class,
            'role' => 'assistant',
            'content' => $response->text,
        ]);

        AgentMessageReceived::dispatch($savedMessage);
    }
}
