<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationTitleUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Constructor method for the Lucius application.
     *
     * @param  Conversation  $conversation  Read-only instance of a Conversation.
     */
    public function __construct(public readonly Conversation $conversation) {}

    /**
     * Determine the channels the event should broadcast on.
     *
     * @return array Array of channels the event will broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversation->id}"),
        ];
    }

    /**
     * Defines the event name to be broadcasted.
     *
     * @return string The name of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return 'title.updated';
    }

    /**
     * Prepare the data to be broadcasted with the event.
     *
     * @return array Associative array containing the conversation ID and title.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->conversation->id,
            'title' => $this->conversation->title,
        ];
    }
}
