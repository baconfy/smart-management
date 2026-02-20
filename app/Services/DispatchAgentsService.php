<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\AgentsProcessing;
use App\Jobs\ProcessAgentMessage;
use App\Models\Conversation;
use Illuminate\Support\Collection;

readonly class DispatchAgentsService
{
    /**
     * Broadcast processing status and dispatch a job for each agent.
     *
     * @param  Collection<int, \App\Models\ProjectAgent>  $agents
     */
    public function __invoke(Conversation $conversation, Collection $agents, string $message): void
    {
        AgentsProcessing::dispatch(
            $conversation,
            $agents->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values()->toArray(),
        );

        $agents->each(
            fn ($agent) => ProcessAgentMessage::dispatch($conversation, $agent, $message),
        );
    }
}
