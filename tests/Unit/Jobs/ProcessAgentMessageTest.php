<?php

declare(strict_types=1);

use App\Ai\Agents\ArchitectAgent;
use App\Enums\AgentType;
use App\Events\AgentMessageReceived;
use App\Jobs\ProcessAgentMessage;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->project = Project::create(['name' => 'Test Project']);
    $this->user = User::factory()->create();
    $this->project->members()->create(['user_id' => $this->user->id, 'role' => 'owner']);

    $this->agent = $this->project->agents()->create(['type' => AgentType::Architect->value, 'name' => 'Architect', 'instructions' => 'You are an architect.']);

    $this->conversation = Conversation::create(['id' => (string) Str::ulid(), 'project_id' => $this->project->id, 'user_id' => $this->user->id, 'title' => 'Architecture Discussion']);
});

test('it calls the agent and stores the response', function () {
    ArchitectAgent::fake(['Use PostgreSQL for this project.']);

    Event::fake([AgentMessageReceived::class]);

    $job = new ProcessAgentMessage(conversation: $this->conversation, projectAgent: $this->agent, message: 'Which database should I use?');
    app()->call([$job, 'handle']);

    ArchitectAgent::assertPrompted(fn () => true);

    expect($this->conversation->messages()->where('role', 'assistant')->count())->toBe(1)
        ->and($this->conversation->messages()->where('role', 'assistant')->first()->content)
        ->toBe('Use PostgreSQL for this project.');
});

test('it broadcasts AgentMessageReceived after saving', function () {
    ArchitectAgent::fake(['Use PostgreSQL.']);
    Event::fake([AgentMessageReceived::class]);

    $job = new ProcessAgentMessage(conversation: $this->conversation, projectAgent: $this->agent, message: 'Which database?');
    app()->call([$job, 'handle']);

    Event::assertDispatched(AgentMessageReceived::class, function ($event) {
        return $event->message->conversation_id === $this->conversation->id
            && $event->message->role === 'assistant';
    });
});

test('it is queued', function () {
    expect(ProcessAgentMessage::class)
        ->toImplement(ShouldQueue::class);
});
