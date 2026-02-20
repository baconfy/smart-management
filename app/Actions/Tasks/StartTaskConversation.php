<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Ai\Stores\ProjectConversationStore;
use App\Enums\AgentType;
use App\Jobs\ProcessAgentMessage;
use App\Models\Conversation;
use App\Models\Task;
use App\Models\User;

class StartTaskConversation
{
    /**
     * Initialize a new instance of the class with a ProjectConversationStore dependency.
     */
    public function __construct(private ProjectConversationStore $store) {}

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
            'Analyze the following task and provide your technical observations, suggestions, and any concerns:',
            'To have a better context, please, read all artifacts like business rules and decisions.',
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

        return implode("\n", $parts);
    }
}
