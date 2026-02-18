<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\ModeratorAgent;
use App\Events\AgentsProcessing;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Constructor for initializing the class with required dependencies.
     *
     * @param  Conversation  $conversation  The conversation instance.
     * @param  Project  $project  The project instance.
     * @param  string  $message  The message content.
     * @param  array  $agentIds  The array of agent IDs.
     */
    public function __construct(public readonly Conversation $conversation, public readonly Project $project, public readonly string $message, public readonly array $agentIds = []) {}

    /**
     * Handles the processing of the conversation by routing through moderators or specified agents.
     *
     * If no agent IDs are provided, the conversation is routed via moderators. Otherwise, it fetches
     * the agents from the project based on the given agent IDs. The method dispatches jobs to process
     * agents' data and handle their messages for the conversation.
     */
    public function handle(): void
    {
        $agents = empty($this->agentIds)
            ? $this->routeViaModerator()
            : $this->project->agents()->whereIn('id', $this->agentIds)->get();

        AgentsProcessing::dispatch($this->conversation, $agents->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values()->toArray());

        $agents->each(
            fn ($agent) => ProcessAgentMessage::dispatch($this->conversation, $agent, $this->message),
        );
    }

    /**
     * Routes the message through a moderator to determine the appropriate agent types.
     *
     * Uses the ModeratorAgent to process the message, filter agents by confidence levels,
     * and retrieve agents matching the determined types from the project's agent pool.
     *
     * @return Collection|array Returns a collection or an array of agents based on the filtered types.
     */
    private function routeViaModerator(): Collection|array
    {
        $moderator = new ModeratorAgent($this->project);
        $result = $moderator->route($this->message);

        $highConfidence = $moderator->highConfidenceAgents($result);

        $types = ! empty($highConfidence)
            ? collect($highConfidence)->pluck('type')->toArray()
            : [collect($result['agents'])->sortByDesc('confidence')->first()['type']];

        return $this->project->agents()->whereIn('type', $types)->get();
    }
}
