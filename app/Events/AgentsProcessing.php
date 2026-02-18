<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentsProcessing implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Constructs a new instance of the class.
     *
     * @param  Conversation  $conversation  The conversation instance.
     * @param  array  $agents  A list of agents associated with the conversation.
     */
    public function __construct(public readonly Conversation $conversation, public readonly array $agents) {}

    /**
     * Retrieves the channels the event should broadcast on.
     *
     * @return array An array of broadcasting channels.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversation->id}"),
        ];
    }

    /**
     * Specifies the name the event should be broadcasted as.
     *
     * @return string The name of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return 'agents.processing';
    }

    /**
     * Prepares the payload for broadcasting.
     *
     * @return array An array containing the broadcast data.
     */
    public function broadcastWith(): array
    {
        return [
            'agents' => $this->agents,
        ];
    }
}
