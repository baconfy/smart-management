<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoutingFailed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Conversation  $conversation  The conversation where routing failed.
     * @param  string  $error  A user-friendly error message.
     */
    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $error,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversation->id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'routing.failed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array{error: string}
     */
    public function broadcastWith(): array
    {
        return [
            'error' => $this->error,
        ];
    }
}
