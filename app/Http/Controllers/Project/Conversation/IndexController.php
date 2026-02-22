<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Conversation;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    /**
     * Display the conversations page, optionally showing a specific conversation.
     */
    public function __invoke(Project $project, ?Conversation $conversation = null): Response
    {
        $this->authorize('view', $project);

        if ($conversation) {
            abort_unless($conversation->project_id === $project->id, 404);
        }

        return Inertia::render('projects/conversations/index', [
            'project' => $project->only('id', 'ulid', 'name', 'description', 'created_at'),
            'agents' => $project->agents()->visible()->orderBy('name')->get(),
            'conversations' => $project->conversations()->whereNull('task_id')->select('id', 'title', 'updated_at')->latest('updated_at')->cursorPaginate(20),
            'conversation' => $conversation?->only('id', 'title', 'created_at', 'updated_at'),
            'messages' => $conversation
                ? $conversation->messages()->whereNull('meta->hidden')->oldest()->cursorPaginate(50)
                : [],
        ]);
    }
}
