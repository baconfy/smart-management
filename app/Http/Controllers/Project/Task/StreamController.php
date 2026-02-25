<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Task;

use App\Actions\ConversationMessages\CreateConversationMessage;
use App\Enums\AgentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StreamChatMessageRequest;
use App\Http\Responses\SseStream;
use App\Models\Project;
use App\Models\Task;
use App\Services\MultiAgentStreamService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamController extends Controller
{
    /**
     * Stream agent responses for task conversations (pre-selects Technical agent).
     */
    public function __invoke(
        StreamChatMessageRequest $request,
        Project $project,
        Task $task,
        CreateConversationMessage $createConversationMessage,
        MultiAgentStreamService $streamService,
    ): StreamedResponse {
        $this->authorize('view', $project);

        abort_unless($task->project_id === $project->id, 404);

        $conversation = $task->conversation;

        abort_unless($conversation !== null, 404);

        $message = $request->validated('message');
        $user = $request->user();

        // Store uploaded files and prepare AI attachments
        /** @var UploadedFile[] $uploadedFiles */
        $uploadedFiles = $request->file('attachments', []);
        $storedAttachments = [];
        $aiAttachments = [];

        foreach ($uploadedFiles as $file) {
            $path = $file->store("conversations/{$conversation->id}", 'public');
            $storedAttachments[] = [
                'filename' => $file->getClientOriginalName(),
                'url' => Storage::disk('public')->url($path),
                'mediaType' => $file->getMimeType(),
                'path' => $path,
            ];

            $mime = $file->getMimeType() ?? '';

            if (str_starts_with($mime, 'image/') || $mime === 'application/pdf') {
                $aiAttachments[] = $file;
            } else {
                // Inline text-based files into the message for the AI
                $content = file_get_contents($file->getRealPath());
                if ($content !== false) {
                    $name = $file->getClientOriginalName();
                    $message .= "\n\n---\n**File: {$name}**\n```\n{$content}\n```";
                }
            }
        }

        // Save user message
        $createConversationMessage($conversation, [
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $request->validated('message'),
            ...($storedAttachments ? ['attachments' => $storedAttachments] : []),
        ]);

        // Pre-select Technical agent (or use provided agent_ids)
        $agentIds = $request->validated('agent_ids', []);

        if (empty($agentIds)) {
            $technicalAgent = $project->agents()->where('type', AgentType::Technical)->first();
            $agents = $technicalAgent ? collect([$technicalAgent]) : collect();
        } else {
            $agents = $project->agents()->whereIn('id', $agentIds)->get();
        }

        return (new SseStream)->through(function () use ($agents, $conversation, $message, $streamService, $aiAttachments): \Generator {
            yield ['type' => 'conversation', 'id' => $conversation->id, 'isNew' => false];

            if ($agents->isEmpty()) {
                yield ['type' => 'error', 'message' => __('No agent available for this task.')];

                return;
            }

            yield [
                'type' => 'routing',
                'agents' => $agents->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'type' => $a->type->value])->values()->toArray(),
                'reasoning' => __('Technical agent selected for task.'),
            ];

            yield from $streamService->stream($agents, $conversation, $message, $aiAttachments);
        })
            ->toResponse();
    }
}
