<?php

declare(strict_types=1);

namespace App\Actions\Conversations;

use App\Models\Conversation;

readonly class DeleteConversation
{
    /**
     * Delete the given conversation.
     */
    public function __invoke(Conversation $conversation): bool
    {
        return $conversation->delete();
    }
}
