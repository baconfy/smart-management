<?php

declare(strict_types=1);

namespace App\Actions\ConversationMessages;

use App\Models\Conversation;
use App\Models\ConversationMessage;

readonly class CreateConversationMessage
{
    /**
     * Create a new message for the given conversation.
     */
    public function __invoke(Conversation $conversation, array $data): ConversationMessage
    {
        return $conversation->messages()->create($data);
    }
}
