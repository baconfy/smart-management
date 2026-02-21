<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatMessageRequest;
use App\Models\Project;
use App\Services\SendChatMessageService;
use Illuminate\Http\RedirectResponse;

class ChatController extends Controller
{
    /**
     * Handle the incoming request to send a chat message for a project conversation.
     */
    public function __invoke(StoreChatMessageRequest $request, Project $project, SendChatMessageService $sendChatMessage): RedirectResponse
    {
        $this->authorize('view', $project);

        $conversation = $sendChatMessage(
            $project,
            $request->user(),
            $request->validated('message'),
            $request->validated('conversation_id'),
            $request->validated('agent_ids', []),
        );

        return to_route('projects.conversations.show', [$project, $conversation->id]);
    }
}
