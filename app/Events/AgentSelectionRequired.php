<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentSelectionRequired implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Constructor method for initializing the Lucius application with required conversation, candidates, and reasoning parameters.
     *
     * @param  Conversation  $conversation  Instance representing the conversation context.
     * @param  array  $candidates  Array of candidate data utilized within the application.
     * @param  string  $reasoning  Explanation or reasoning associated with the operation.
     */
    public function __construct(public readonly Conversation $conversation, public readonly array $candidates, public readonly string $reasoning) {}

    /**
     * Determines the private channel on which the event should be broadcast.
     *
     * @return PrivateChannel The private channel instance associated with the specific conversation.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("conversation.{$this->conversation->id}");
    }

    /**
     * Defines the event name for broadcasting purposes.
     *
     * @return string The name of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return 'agent.selection.required';
    }

    /**
     * Prepares the data to be included when broadcasting an event.
     *
     * @return array Associated array containing 'candidates' and 'reasoning' data for the broadcast payload.
     */
    public function broadcastWith(): array
    {
        return [
            'candidates' => $this->candidates,
            'reasoning' => $this->reasoning,
        ];
    }
}
