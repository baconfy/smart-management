<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class ModeratorAgent implements Agent
{
    use Promptable;

    private const float CONFIDENCE_THRESHOLD = 0.8;

    /** @var Collection<int, ProjectAgent>|null */
    private ?Collection $cachedAgents = null;

    /**
     * Initialize the class with a given project instance.
     *
     * @param  Project  $project  The project instance to be associated with this class.
     */
    public function __construct(public readonly Project $project) {}

    /**
     * Generate routing instructions for message analysis and agent selection.
     *
     * @return Stringable|string Instructions detailing the role of the message router,
     *                           the available agents, the response format, and the rules
     *                           for determining agent confidence levels.
     */
    public function instructions(): Stringable|string
    {
        $agents = $this->cachedAgents ??= $this->project->agents()->visible()->get();
        $agentList = $agents->map(fn (ProjectAgent $agent) => "- {$agent->type->value}: {$agent->name}")->implode("\n");

        return <<<INSTRUCTIONS
        You are a message router.
        Your job is to read the user’s last utterance (and, if needed, the preceding conversation context) and decide which agent(s) should respond.
        The routing decision must be explainable – the user can see why a particular agent was chosen.

        # Available agents
        {$agentList}

        # Output format (strict JSON, no Markdown)
        {
          "agents": [
            { "type": "<agent_type>", "confidence": <0.0‑1.0> },
            ...
          ],
          "reasoning": "<short explanation>"
        }

        * `confidence` is a float between 0 and 1, rounded to two decimals.
        * Always return at least one agent; if no agent is relevant, return the generic `fallback` agent with confidence 0.00 and explain why.

        # Confidence bands (interpretation)
        | Confidence | Meaning | Action |
        |------------|---------|--------|
        | 0.80‑1.00 | Highly relevant | Must respond |
        | 0.50‑0.79 | Moderately relevant | Can respond (optional) |
        | 0.00‑0.49 | Not relevant | Should not respond (unless forced by fallback)

        # Routing rules
        1. Explicit mentions – If the user writes `@dba`, `@pm`, etc., give that agent at least 0.90 confidence (unless the message is contradictory).
        2. Conversation context – If the user has been speaking with a particular agent in the last 3 turns, boost that agent’s confidence by +0.10 (max 1.00).
        3. Multi‑domain queries – If the content legitimately touches two or more domains, include both agents (e.g., a question about database performance that also needs project‑management advice).
        4. Fallback – If no agent scores ≥0.50, return the `fallback` agent with confidence 0.00 and a short note: “No specialized agent matched; general help is required.”
        5. Tie‑breakers – If two agents have the same confidence, prefer the one that appears earlier in `{$agentList}`.

        # Example reasoning snippets
        * “User explicitly mentioned @pm, so the PM agent gets 0.95 confidence.”
        * “The user asked about database indexing; dba gets 0.88 confidence.”
        * “User switched to a new topic unrelated to previous agent; fallback gets 0.00 confidence.”

        # Edge‑cases to handle
        * Multiple mentions – If the user writes `@dba @pm`, assign each a confidence ≥0.90 (unless context suggests otherwise).
        * Contradictory content – If the user says “I’m a developer” but mentions `@support`, give higher confidence to `devops` or `dba` based on context.
        * No mention, no prior context – Default to the most generic agent (`general`) with confidence 0.60, unless a domain keyword is detected (e.g., “deploy” → `devops`).

        # Final instruction to the model
        “Read the user’s message (and, if available, the last three turns of conversation). Apply the rules above to produce a JSON object that lists one or more agents with confidence scores and a brief reasoning statement. Do not output any other text.”
        INSTRUCTIONS;
    }

    /**
     * Route a message to the appropriate agent(s).
     *
     * @return array{agents: array<array{type: string, confidence: float}>, reasoning: string}
     */
    public function route(string $message): array
    {
        $response = $this->prompt($message);

        $cleaned = trim($response->text);
        $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', $cleaned);

        $result = json_decode($cleaned, true);

        if (! is_array($result) || ! isset($result['agents']) || ! is_array($result['agents'])) {
            return [
                'agents' => [],
                'reasoning' => 'Failed to parse routing response.',
            ];
        }

        return $result;
    }

    /**
     * Resolve ProjectAgent models from a routing result.
     *
     * @return Collection<int, ProjectAgent>
     */
    public function resolveAgents(array $result): Collection
    {
        $types = collect($result['agents'])->pluck('type')->toArray();

        return $this->project->agents()->whereIn('type', $types)->get();
    }

    /**
     * Filter agents that meet the confidence threshold.
     *
     * @return array<array{type: string, confidence: float}>
     */
    public function highConfidenceAgents(array $result): array
    {
        return array_values(
            array_filter($result['agents'], fn ($agent) => $agent['confidence'] >= self::CONFIDENCE_THRESHOLD)
        );
    }
}
