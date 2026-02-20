<?php

declare(strict_types=1);

namespace App\Actions\Conversations;

use App\Models\Conversation;
use App\Models\Project;

readonly class CreateConversation
{
    /**
     * Create a new conversation for the given project.
     */
    public function __invoke(Project $project, array $data): Conversation
    {
        return $project->conversations()->create($data);
    }
}
