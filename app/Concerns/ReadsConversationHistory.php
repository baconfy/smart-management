<?php

declare(strict_types=1);

namespace App\Concerns;

/**
 * Allows reading conversation history without triggering the SDK's
 * RememberConversation middleware (which would store duplicate messages).
 *
 * Use this when the controller manages message storage manually,
 * such as in multi-agent scenarios.
 */
trait ReadsConversationHistory
{
    /**
     * Set conversation ID for reading history only.
     *
     * Unlike continue(), this does NOT set conversationUser,
     * so RememberConversation middleware won't run.
     * The agent can still read messages via messages().
     */
    public function withConversationHistory(string $conversationId): static
    {
        $this->conversationId = $conversationId;

        return $this;
    }
}
