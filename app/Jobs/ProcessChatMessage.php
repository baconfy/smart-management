<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\ModeratorAgent;
use App\Events\AgentSelectionRequired;
use App\Models\Conversation;
use App\Models\Project;
use App\Services\DispatchAgentsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Initialize a new instance of the class.
     */
    public function __construct(public readonly Conversation $conversation, public readonly Project $project, public readonly string $message, public readonly array $agentIds = []) {}

    /**
     * Handle the processing of the instance.
     */
    public function handle(DispatchAgentsService $dispatchAgents): void
    {
        if (! empty($this->agentIds)) {
            $dispatchAgents(
                $this->conversation,
                $this->project->agents()->whereIn('id', $this->agentIds)->get(),
                $this->message,
            );

            return;
        }

        $this->routeViaModerator($dispatchAgents);
    }

    /**
     * Routes the message through a moderator and dispatches agents for handling.
     */
    private function routeViaModerator(DispatchAgentsService $dispatchAgents): void
    {
        $moderator = new ModeratorAgent($this->project);
        $result = $moderator->route($this->message);

        $highConfidence = $moderator->highConfidenceAgents($result);

        if (! empty($highConfidence)) {
            $types = collect($highConfidence)->pluck('type')->toArray();
            $agents = $this->project->agents()->whereIn('type', $types)->get();
            $dispatchAgents($this->conversation, $agents, $this->message);

            return;
        }

        AgentSelectionRequired::dispatch(
            $this->conversation,
            $result['agents'],
            $result['reasoning'] ?? _('I need your help deciding who should answer this.'),
        );
    }
}
