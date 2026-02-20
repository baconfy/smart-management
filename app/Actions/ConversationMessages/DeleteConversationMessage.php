<?php

declare(strict_types=1);

namespace App\Actions\ConversationMessages;

use App\Models\ConversationMessage;

readonly class DeleteConversationMessage
{
    /**
     * Delete the given conversation message.
     */
    public function __invoke(ConversationMessage $message): bool
    {
        return $message->delete();
    }
}
