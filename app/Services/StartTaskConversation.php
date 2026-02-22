<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\ConversationMessages\CreateConversationMessage;
use App\Actions\Conversations\UpdateConversation;
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
    public function __construct(
        private ProjectConversationStore $store,
        private UpdateConversation $updateConversation,
        private CreateConversationMessage $createConversationMessage,
    ) {}

    /**
     * Handle the invocation to either retrieve an existing conversation for the task
     * or create a new one, associate it with the task, and process agent messaging.
     */
    public function __invoke(Task $task, User $user): Conversation
    {
        if ($task->refresh()->conversation) {
            return $task->conversation;
        }

        [$project, $conversation] = $this->setupTask($task, $user);
        $this->moveTaskToInProgress($project, $task);

        $message = $this->buildTaskContext($task);
        $this->createMessage($conversation, $user, $message, $project->agents()->whereType(AgentType::Technical)->first());

        $this->store->reset();

        return $conversation;
    }

    /**
     * Assigns a task to a user and creates a conversation for the task within the project.
     */
    private function setupTask(Task $task, User $user): array
    {
        $conversation = $this->store->forProject($task->project)->createConversation($user->id, $task->title);
        ($this->updateConversation)($conversation, ['task_id' => $task->id]);

        return [$task->project, $conversation];
    }

    /**
     * Move the specified task to the "In Progress" status within the given project.
     */
    private function moveTaskToInProgress(mixed $project, Task $task): void
    {
        $inProgress = $project->statuses()->where('is_in_progress', true)->first();
        if ($inProgress) {
            $task->update(['task_status_id' => $inProgress->id]);
        }
    }

    /**
     * Create a new message in the specified conversation and dispatch the processing of a technical agent message.
     */
    private function createMessage(mixed $conversation, User $user, string $message, $technicalAgent): void
    {
        ($this->createConversationMessage)($conversation, [
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $message,
            'meta' => ['hidden' => true],
        ]);

        ProcessAgentMessage::dispatch($conversation, $technicalAgent, $message);
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
        $parts[] = 'Always respond in the same language as the task title and description.';
        $parts[] = 'Always respond as short as possible.';

        return implode("\n", $parts);
    }
}
