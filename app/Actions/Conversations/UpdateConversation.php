<?php

declare(strict_types=1);

namespace App\Actions\Conversations;

use App\Models\Conversation;

readonly class UpdateConversation
{
    /**
     * Update the given conversation.
     */
    public function __invoke(Conversation $conversation, array $payload): bool
    {
        return $conversation->update($payload);
    }
}
