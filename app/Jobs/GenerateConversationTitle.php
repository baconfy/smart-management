<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\ModeratorAgent;
use App\Events\ConversationTitleUpdated;
use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GenerateConversationTitle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the queued job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the queued job may run before timing out.
     */
    public int $timeout = 30;

    /**
     * Constructor method to initialize the Conversation instance.
     */
    public function __construct(public readonly Conversation $conversation) {}

    /**
     * Generate an AI-powered title for the conversation from the first user message.
     */
    public function handle(): void
    {
        $firstMessage = $this->conversation->messages()->where('role', 'user')->oldest()->value('content');
        if (! $firstMessage) {
            return;
        }

        try {
            $moderator = new ModeratorAgent($this->conversation->project);
            $response = $moderator->prompt("Generate a short, descriptive title (max 60 characters) for a conversation that starts with this message. Return ONLY the title, no quotes, no explanation:\n\n{$firstMessage}");
        } catch (Throwable $e) {
            Log::warning('Failed to generate conversation title', ['conversation_id' => $this->conversation->id, 'error' => $e->getMessage()]);

            return;
        }

        $title = Str::limit(trim($response->text, " \"\n"), 60, preserveWords: true);
        if (empty($title)) {
            return;
        }

        $this->conversation->update(['title' => $title]);

        ConversationTitleUpdated::dispatch($this->conversation);
    }
}
