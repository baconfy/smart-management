<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Conversation;

use App\Actions\ConversationMessages\CreateConversationMessage;
use App\Actions\Conversations\CreateConversation;
use App\Ai\Agents\ModeratorAgent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StreamChatMessageRequest;
use App\Http\Responses\SseStream;
use App\Models\Conversation;
use App\Models\Project;
use App\Services\MultiAgentStreamService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StreamController extends Controller
{
    /**
     * Handle streaming chat message with SSE.
     */
    public function __invoke(
        StreamChatMessageRequest $request,
        Project $project,
        CreateConversation $createConversation,
        CreateConversationMessage $createConversationMessage,
        MultiAgentStreamService $streamService,
        ?Conversation $conversation = null,
    ): StreamedResponse {
        $this->authorize('view', $project);

        $message = $request->validated('message');
        $agentIds = $request->validated('agent_ids', []);
        $user = $request->user();
        $isNew = $conversation === null;

        // Resolve or create conversation
        if ($isNew) {
            $conversation = $createConversation($project, [
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'title' => Str::limit($message, 100, preserveWords: true),
            ]);
        }

        // Save user message
        $createConversationMessage($conversation, [
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $message,
        ]);

        return (new SseStream)
            ->through(function () use ($project, $conversation, $message, $agentIds, $isNew, $streamService): \Generator {
                // 1. Conversation event
                yield ['type' => 'conversation', 'id' => $conversation->id, 'isNew' => $isNew];

                // 2. Determine agents
                if (! empty($agentIds)) {
                    $agents = $project->agents()->whereIn('id', $agentIds)->get();

                    yield [
                        'type' => 'routing',
                        'agents' => $agents->map(fn ($a) => [
                            'id' => $a->id,
                            'name' => $a->name,
                            'type' => $a->type->value,
                        ])->values()->toArray(),
                        'reasoning' => 'Agents selected by user.',
                    ];
                } else {
                    // Run moderator synchronously
                    try {
                        $moderator = new ModeratorAgent($project);
                        $result = $moderator->route($message);
                        $highConfidence = $moderator->highConfidenceAgents($result);

                        if (! empty($highConfidence)) {
                            $types = collect($highConfidence)->pluck('type')->toArray();
                            $agents = $project->agents()->whereIn('type', $types)->get();

                            yield [
                                'type' => 'routing',
                                'agents' => $agents->map(fn ($a) => [
                                    'id' => $a->id,
                                    'name' => $a->name,
                                    'type' => $a->type->value,
                                ])->values()->toArray(),
                                'reasoning' => $result['reasoning'] ?? '',
                            ];
                        } else {
                            // Low confidence — send routing poll and close
                            $allAgents = $moderator->resolveAgents($result);

                            yield [
                                'type' => 'routing_poll',
                                'reasoning' => $result['reasoning'] ?? "I'm not sure which agent should handle this.",
                                'candidates' => collect($result['agents'])->map(function ($agentData) use ($allAgents) {
                                    $resolved = $allAgents->first(fn ($a) => $a->type->value === $agentData['type']);

                                    return $resolved ? [
                                        'id' => $resolved->id,
                                        'name' => $resolved->name,
                                        'type' => $agentData['type'],
                                        'confidence' => $agentData['confidence'],
                                    ] : null;
                                })->filter()->values()->toArray(),
                            ];

                            return;
                        }
                    } catch (Throwable $e) {
                        Log::error('Moderator routing failed during stream', [
                            'conversation_id' => $conversation->id,
                            'project_id' => $project->id,
                            'error' => $e->getMessage(),
                        ]);

                        yield ['type' => 'error', 'message' => 'Failed to route your message. Please try again.'];

                        return;
                    }
                }

                // 3. Stream agent responses
                yield from $streamService->stream($agents, $conversation, $message);
            })
            ->toResponse();
    }
}
