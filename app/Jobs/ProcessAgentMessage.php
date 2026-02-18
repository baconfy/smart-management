<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\GenericAgent;
use App\Ai\Stores\ProjectConversationStore;
use App\Events\AgentMessageReceived;
use App\Models\Conversation;
use App\Models\ProjectAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Ai\Contracts\Agent;

class ProcessAgentMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Class constructor for initializing conversation, project agent, and message.
     *
     * @param  Conversation  $conversation  Instance of the Conversation class.
     * @param  ProjectAgent  $projectAgent  Instance of the ProjectAgent class.
     * @param  string  $message  The message associated with the instance.
     */
    public function __construct(public readonly Conversation $conversation, public readonly ProjectAgent $projectAgent, public readonly string $message) {}

    /**
     * Handles the process of storing a conversation message and dispatching the appropriate event.
     *
     * @param  ProjectConversationStore  $store  The service responsible for storing conversation messages.
     */
    public function handle(ProjectConversationStore $store): void
    {
        $agent = GenericAgent::make(projectAgent: $this->projectAgent);
        $agent->withConversationHistory($this->conversation->id);

        $response = $agent->prompt($this->message, model: $this->projectAgent->model);

        $store->forProject($this->conversation->project);

        $savedMessage = $store->storeRawAssistantMessage($this->conversation->id, $this->conversation->user_id, $this->projectAgent->id, $agent::class, $response->text);

        AgentMessageReceived::dispatch($savedMessage);
    }
}
