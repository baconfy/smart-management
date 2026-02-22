<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\ConversationMessages\CreateConversationMessage;
use App\Ai\Agents\GenericAgent;
use App\Jobs\GenerateConversationTitle;
use App\Models\Conversation;
use App\Models\ProjectAgent;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Streaming\Events\TextDelta;
use Throwable;

readonly class MultiAgentStreamService
{
    public function __construct(private CreateConversationMessage $createConversationMessage) {}

    /**
     * Stream responses from multiple agents, multiplexed over SSE.
     *
     * Uses round-robin polling of generators. Each agent streams sequentially
     * within a cooperative loop. Yields SSE event arrays.
     *
     * @param  Collection<int, ProjectAgent>  $agents
     */
    public function stream(Collection $agents, Conversation $conversation, string $message): Generator
    {
        // Send agent_start for all agents upfront
        foreach ($agents as $agent) {
            yield ['type' => 'agent_start', 'agentId' => $agent->id, 'name' => $agent->name];
        }

        // Build streams for each agent
        /** @var array<int, array{stream: Generator, buffer: string, agent: ProjectAgent}> */
        $activeStreams = [];
        $titleDispatched = false;

        foreach ($agents as $agent) {
            try {
                $genericAgent = GenericAgent::make(projectAgent: $agent);
                $genericAgent->withConversationHistory($conversation->id);

                $streamable = $genericAgent->stream($message, model: $agent->model);
                $iterator = $streamable->getIterator();
                $iterator->rewind();

                $activeStreams[$agent->id] = [
                    'stream' => $iterator,
                    'buffer' => '',
                    'agent' => $agent,
                ];
            } catch (Throwable $e) {
                Log::error('Failed to start agent stream', [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'error' => $e->getMessage(),
                ]);

                yield [
                    'type' => 'agent_error',
                    'agentId' => $agent->id,
                    'message' => 'Failed to start agent: '.$agent->name,
                ];
            }
        }

        // Round-robin poll all active streams
        while (! empty($activeStreams)) {
            foreach ($activeStreams as $agentId => $state) {
                if (connection_aborted()) {
                    return;
                }

                $iterator = $state['stream'];

                if (! $iterator->valid()) {
                    // Stream finished — save response and emit agent_done
                    $savedMessage = $this->saveAgentResponse(
                        $conversation,
                        $state['agent'],
                        $state['buffer'],
                    );

                    yield [
                        'type' => 'agent_done',
                        'agentId' => $agentId,
                        'messageId' => $savedMessage->id,
                    ];

                    if (! $titleDispatched) {
                        $titleDispatched = true;
                        GenerateConversationTitle::dispatch($conversation);
                    }

                    unset($activeStreams[$agentId]);

                    continue;
                }

                try {
                    $event = $iterator->current();
                    $iterator->next();

                    if ($event instanceof TextDelta) {
                        $activeStreams[$agentId]['buffer'] .= $event->delta;

                        yield [
                            'type' => 'chunk',
                            'agentId' => $agentId,
                            'text' => $event->delta,
                        ];
                    }
                } catch (Throwable $e) {
                    Log::error('Agent stream error', [
                        'agent_id' => $agentId,
                        'agent_name' => $state['agent']->name,
                        'error' => $e->getMessage(),
                    ]);

                    // Save partial response if any
                    if (! empty($activeStreams[$agentId]['buffer'])) {
                        $this->saveAgentResponse(
                            $conversation,
                            $state['agent'],
                            $activeStreams[$agentId]['buffer'],
                        );
                    }

                    yield [
                        'type' => 'agent_error',
                        'agentId' => $agentId,
                        'message' => 'Agent '.$state['agent']->name.' encountered an error.',
                    ];

                    unset($activeStreams[$agentId]);
                }
            }
        }
    }

    /**
     * Save the completed agent response to the database.
     */
    private function saveAgentResponse(Conversation $conversation, ProjectAgent $agent, string $content): \App\Models\ConversationMessage
    {
        return ($this->createConversationMessage)($conversation, [
            'id' => (string) Str::ulid(),
            'user_id' => $conversation->user_id,
            'project_agent_id' => $agent->id,
            'agent' => $agent->name,
            'role' => 'assistant',
            'content' => $content,
        ]);
    }
}
