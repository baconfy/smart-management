<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\ConversationTitleUpdated;
use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateConversationTitle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
    ) {}

    public function handle(): void
    {
        // TODO: Replace with AI-generated title via Moderator (cheap model)
        $title = Str::limit($this->conversation->title, 60, preserveWords: true);

        $this->conversation->update(['title' => $title]);

        ConversationTitleUpdated::dispatch($this->conversation);
    }
}
