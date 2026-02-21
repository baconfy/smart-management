<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Task;

use App\Actions\ConversationMessages\CreateConversationMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatMessageRequest;
use App\Jobs\ProcessChatMessage;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Handle the incoming request to store a chat message for a specific task within a project.
     */
    public function __invoke(StoreChatMessageRequest $request, Project $project, Task $task, CreateConversationMessage $createConversationMessage): RedirectResponse
    {
        $this->authorize('view', $project);

        abort_unless($task->project_id === $project->id, 404);

        $conversation = $task->conversation;

        abort_unless($conversation !== null, 404);

        $createConversationMessage($conversation, [
            'id' => (string) Str::ulid(),
            'user_id' => $request->user()->id,
            'role' => 'user',
            'content' => $request->validated('message'),
        ]);

        ProcessChatMessage::dispatch($conversation, $project, $request->validated('message'), $request->validated('agent_ids', []));

        return back();
    }
}
