<?php

declare(strict_types=1);

use App\Ai\Stores\ProjectConversationStore;
use App\Enums\AgentType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Promptable;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;

// ============================================================================
// Binding
// ============================================================================

test('container resolves our custom conversation store', function (): void {
    expect(resolve(ConversationStore::class))
        ->toBeInstanceOf(ProjectConversationStore::class);
});

// ============================================================================
// Store Conversation
// ============================================================================

test('stores conversation with project_id', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $user = User::factory()->create();

    $store = resolve(ConversationStore::class);
    $store->forProject($project);

    $conversationId = $store->storeConversation($user->id, 'Architecture Discussion');

    $conversation = DB::table('agent_conversations')->where('id', $conversationId)->first();

    expect($conversation)
        ->project_id->toBe($project->id)
        ->title->toBe('Architecture Discussion')
        ->user_id->toBe($user->id);
});

// ============================================================================
// Store Messages
// ============================================================================

test('stores assistant message with project_agent_id', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);
    $user = User::factory()->create();

    $store = resolve(ConversationStore::class);
    $store->forProject($project)->withAgent($agent);

    $conversationId = $store->storeConversation($user->id, 'Test Chat');

    $prompt = createFakePrompt('What database should I use?');
    $response = createFakeResponse('I recommend PostgreSQL.');

    $store->storeUserMessage($conversationId, $user->id, $prompt);
    $messageId = $store->storeAssistantMessage($conversationId, $user->id, $prompt, $response);

    $message = DB::table('agent_conversation_messages')->where('id', $messageId)->first();

    expect($message)
        ->project_agent_id->toBe($agent->id)
        ->role->toBe('assistant');
});

test('stores user message with null project_agent_id', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $user = User::factory()->create();

    $store = resolve(ConversationStore::class);
    $store->forProject($project);

    $conversationId = $store->storeConversation($user->id, 'Test Chat');

    $prompt = createFakePrompt('What database should I use?');

    $messageId = $store->storeUserMessage($conversationId, $user->id, $prompt);

    $message = DB::table('agent_conversation_messages')->where('id', $messageId)->first();

    expect($message)
        ->project_agent_id->toBeNull()
        ->role->toBe('user');
});

// ============================================================================
// Latest Conversation (scoped by project)
// ============================================================================

test('latest conversation id is scoped by project', function (): void {
    $user = User::factory()->create();
    $projectA = Project::create(['name' => 'Project A']);
    $projectB = Project::create(['name' => 'Project B']);

    $store = resolve(ConversationStore::class);

    $store->forProject($projectA);
    $store->storeConversation($user->id, 'Chat A');

    $store->forProject($projectB);
    $convB = $store->storeConversation($user->id, 'Chat B');

    $store->forProject($projectB);
    $latestId = $store->latestConversationId($user->id);

    expect($latestId)->toBe($convB);
});

// ============================================================================
// Context Reset
// ============================================================================

test('reset clears agent context between operations', function (): void {
    $project = Project::create(['name' => 'Test Project']);
    $agent = $project->agents()->create([
        'type' => AgentType::Architect->value,
        'name' => 'Architect',
        'instructions' => 'You are an architect.',
    ]);
    $user = User::factory()->create();

    $store = resolve(ConversationStore::class);
    $store->forProject($project)->withAgent($agent);

    $conversationId = $store->storeConversation($user->id, 'With Agent');

    $prompt = createFakePrompt('Test');
    $response = createFakeResponse('Response');
    $msgWithAgent = $store->storeAssistantMessage($conversationId, $user->id, $prompt, $response);

    // Reset agent context, keep project
    $store->reset()->forProject($project);

    $msgWithoutAgent = $store->storeAssistantMessage($conversationId, $user->id, $prompt, $response);

    expect(DB::table('agent_conversation_messages')->where('id', $msgWithAgent)->first())
        ->project_agent_id->toBe($agent->id);

    expect(DB::table('agent_conversation_messages')->where('id', $msgWithoutAgent)->first())
        ->project_agent_id->toBeNull();
});

// ============================================================================
// Helpers
// ============================================================================

function createFakePrompt(string $text): AgentPrompt
{
    $agent = new class implements Agent
    {
        use Promptable;

        public function instructions(): string
        {
            return 'Test agent';
        }
    };

    $provider = Mockery::mock(TextProvider::class);
    $provider->shouldReceive('cheapestTextModel')->andReturn('fake-model');

    return new AgentPrompt(
        agent: $agent,
        prompt: $text,
        attachments: [],
        provider: $provider,
        model: 'fake-model',
    );
}

function createFakeResponse(string $text): AgentResponse
{
    return new AgentResponse(
        invocationId: (string) \Illuminate\Support\Str::uuid(),
        text: $text,
        usage: new Usage,
        meta: new Meta,
    );
}
