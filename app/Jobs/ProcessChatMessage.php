<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\ModeratorAgent;
use App\Events\AgentSelectionRequired;
use App\Events\AgentsProcessing;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProcessChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Initialize a new instance of the class.
     *
     * @param  Conversation  $conversation  The conversation related to the instance.
     * @param  Project  $project  The project associated with the instance.
     * @param  string  $message  The message content.
     * @param  array  $agentIds  A list of agent IDs.
     */
    public function __construct(public readonly Conversation $conversation, public readonly Project $project, public readonly string $message, public readonly array $agentIds = []) {}

    /**
     * Handle the processing of the instance.
     *
     * Executes specific logic based on the presence of agent IDs. If agent IDs are provided,
     * dispatches the task to the corresponding agents. Otherwise, routes the task through a moderator.
     */
    public function handle(): void
    {
        if (! empty($this->agentIds)) {
            $this->dispatchToAgents(
                $this->project->agents()->whereIn('id', $this->agentIds)->get()
            );

            return;
        }

        $this->routeViaModerator();
    }

    /**
     * Routes the message through a moderator and dispatches agents for handling.
     *
     * Evaluates agent confidence levels and determines if high-confidence agents
     * are available to handle the task. If available, appropriate agents are dispatched.
     * Otherwise, an event is triggered to request the user to select an agent.
     */
    private function routeViaModerator(): void
    {
        $moderator = new ModeratorAgent($this->project);
        $result = $moderator->route($this->message);

        $highConfidence = $moderator->highConfidenceAgents($result);

        if (! empty($highConfidence)) {
            $types = collect($highConfidence)->pluck('type')->toArray();
            $agents = $this->project->agents()->whereIn('type', $types)->get();
            $this->dispatchToAgents($agents);

            return;
        }

        // No high-confidence agents — ask the user to choose
        AgentSelectionRequired::dispatch(
            $this->conversation,
            $result['agents'],
            $result['reasoning'] ?? _('I need your help deciding who should answer this.'),
        );
    }

    /**
     * Dispatch tasks to the agents for processing.
     *
     * @param  mixed  $agents  A collection of agents to which tasks will be dispatched.
     */
    private function dispatchToAgents(Collection $agents): void
    {
        AgentsProcessing::dispatch($this->conversation, $agents->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values()->toArray());

        $agents->each(
            fn ($agent) => ProcessAgentMessage::dispatch(
                $this->conversation,
                $agent,
                $this->message,
            ),
        );
    }
}
