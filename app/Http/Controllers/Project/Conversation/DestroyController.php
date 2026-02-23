<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Conversation;

use App\Actions\Conversations\DeleteConversation;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    /**
     * Delete a conversation.
     */
    public function __invoke(Request $request, Project $project, Conversation $conversation, DeleteConversation $deleteConversation): RedirectResponse
    {
        $this->authorize('view', $project);

        $deleteConversation($conversation);

        return to_route('projects.conversations.index', $project);
    }
}
