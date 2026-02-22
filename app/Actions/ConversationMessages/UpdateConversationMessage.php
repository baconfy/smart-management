<?php

declare(strict_types=1);

namespace App\Actions\ConversationMessages;

use App\Models\ConversationMessage;

readonly class UpdateConversationMessage
{
    /**
     * Update the given conversation message.
     */
    public function __invoke(ConversationMessage $message, array $payload): bool
    {
        return $message->update($payload);
    }
}
