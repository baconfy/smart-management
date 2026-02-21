<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Actions\ConversationMessages\CreateConversationMessage;
use App\Ai\Stores\ProjectConversationStore;
use App\Enums\AgentType;
use App\Jobs\ProcessAgentMessage;
use App\Models\Conversation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;

readonly class StartTaskConversation
{
    /**
     * Initialize a new instance of the class with dependencies.
     */
    public function __construct(private ProjectConversationStore $store, private CreateConversationMessage $createConversationMessage) {}

    /**
     * Handle the invocation to either retrieve an existing conversation for the task
     * or create a new one, associate it with the task, and process agent messaging.
     */
    public function __invoke(Task $task, User $user): Conversation
    {
        if ($task->refresh()->conversation) {
            return $task->conversation;
        }

        $project = $task->project;
        $conversation = $this->store->forProject($project)->createConversation($user->id, $task->title);
        $conversation->update(['task_id' => $task->id]);

        $technicalAgent = $project->agents()->where('type', AgentType::Technical)->first();
        $message = $this->buildTaskContext($task);

        ($this->createConversationMessage)($conversation, [
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $message,
            'meta' => ['hidden' => true],
        ]);

        ProcessAgentMessage::dispatch($conversation, $technicalAgent, $message);

        $this->store->reset();

        return $conversation;
    }

    /**
     * Build a textual context description for a given task, including key details
     * such as title, description, priority, phase, and estimate.
     */
    private function buildTaskContext(Task $task): string
    {
        $parts = [
            'Analyze the following task and create an action plan for implementation.',
            'Based on the artifacts like business rules and decisions.',
            'Provide your technical observations, suggestions, and any concerns about this task.',
            '',
            "**Title:** {$task->title}",
            "**Description:** {$task->description}",
        ];

        if ($task->priority) {
            $parts[] = "**Priority:** {$task->priority->value}";
        }

        if ($task->phase) {
            $parts[] = "**Phase:** {$task->phase}";
        }

        if ($task->estimate) {
            $parts[] = "**Estimate:** {$task->estimate}";
        }

        $parts[] = '';
        $parts[] = 'Based on the above, provide:';
        $parts[] = '1. A step-by-step action plan for implementing this task';
        $parts[] = '2. Key technical decisions and trade-offs';
        $parts[] = '3. Potential risks or blockers';
        $parts[] = '4. Suggested subtasks breakdown if applicable';
        $parts[] = '';
        $parts[] = 'Always respond in the same language as the task title and description.';

        return implode("\n", $parts);
    }
}
