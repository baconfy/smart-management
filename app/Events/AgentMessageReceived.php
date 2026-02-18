<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ConversationMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Constructor method for initializing the ConversationMessage.
     *
     * @param  ConversationMessage  $message  The conversation message instance.
     */
    public function __construct(public readonly ConversationMessage $message) {}

    /**
     * Determine the channels the event should broadcast on.
     *
     * @return array The list of broadcasting channels.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->message->conversation_id}"),
        ];
    }

    /**
     * Specifies the name of the event when broadcasted.
     *
     * @return string The event name to be used for broadcasting.
     */
    public function broadcastAs(): string
    {
        return 'message.received';
    }

    /**
     * Prepares the data to be broadcasted with the event.
     *
     * @return array The array of message data to be sent with the broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'role' => $this->message->role,
                'content' => $this->message->content,
                'agent' => $this->message->agent,
                'project_agent_id' => $this->message->project_agent_id,
                'created_at' => $this->message->created_at?->toISOString(),
            ],
        ];
    }
}
