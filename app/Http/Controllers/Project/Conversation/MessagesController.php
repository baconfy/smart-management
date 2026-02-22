<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Conversation;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class MessagesController extends Controller
{
    /**
     * Load paginated messages for a conversation.
     */
    public function __invoke(Project $project, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $project);

        abort_unless($conversation->project_id === $project->id, 404);

        $messages = $conversation->messages()
            ->whereNull('meta->hidden')
            ->oldest()
            ->cursorPaginate(50);

        return response()->json($messages);
    }
}
