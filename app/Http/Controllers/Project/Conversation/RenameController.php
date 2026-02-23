<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Conversation;

use App\Actions\Conversations\UpdateConversation;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RenameController extends Controller
{
    /**
     * Rename a conversation.
     */
    public function __invoke(Request $request, Project $project, Conversation $conversation, UpdateConversation $updateConversation): RedirectResponse
    {
        $this->authorize('view', $project);

        $updateConversation($conversation, $request->validate(['title' => ['required', 'string', 'max:255']]));

        return back();
    }
}
