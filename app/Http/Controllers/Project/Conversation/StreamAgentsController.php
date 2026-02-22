<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Requests\StreamAgentsRequest;
use App\Http\Responses\SseStream;
use App\Models\Conversation;
use App\Models\Project;
use App\Services\MultiAgentStreamService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamAgentsController extends Controller
{
    /**
     * Stream responses from user-selected agents (after routing poll).
     */
    public function __invoke(
        StreamAgentsRequest $request,
        Project $project,
        Conversation $conversation,
        MultiAgentStreamService $streamService,
    ): StreamedResponse {
        $this->authorize('view', $project);

        $agents = $project->agents()->whereIn('id', $request->validated('agent_ids'))->get();

        $lastUserMessage = $conversation->messages()
            ->where('role', 'user')
            ->latest()
            ->value('content');

        abort_unless($lastUserMessage !== null, 422, 'No user message found in conversation.');

        return (new SseStream)
            ->through(function () use ($agents, $conversation, $lastUserMessage, $streamService): \Generator {
                yield [
                    'type' => 'routing',
                    'agents' => $agents->map(fn ($a) => [
                        'id' => $a->id,
                        'name' => $a->name,
                        'type' => $a->type->value,
                    ])->values()->toArray(),
                    'reasoning' => 'Agents selected by user.',
                ];

                yield from $streamService->stream($agents, $conversation, $lastUserMessage);
            })
            ->toResponse();
    }
}
