<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Support\Collection;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class ModeratorAgent implements Agent
{
    use Promptable;

    private const float CONFIDENCE_THRESHOLD = 0.8;

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
        $agents = $this->project->agents()->get();
        $agentList = $agents->map(fn (ProjectAgent $agent) => "- {$agent->type->value}: {$agent->name}")->implode("\n");

        return <<<INSTRUCTIONS
        You are a deterministic message router.

        Your job is to analyze the user's message and decide which agent(s) should respond.

        You MUST respond ONLY with a valid JSON object.
        No markdown.
        No backticks.
        No explanations outside the JSON.

        Output format (STRICT):

        {
            "agents": [
                { "type": "agent_type", "confidence": 0.0-1.0 }
            ],
            "reasoning": "Brief explanation of why these agents were chosen."
        }

        --------------------------------------------------
        AVAILABLE AGENTS:
        {$agentList}
        --------------------------------------------------

        ROUTING INTELLIGENCE RULES

        1. Always include at least one agent.

        2. Confidence scale:
           - 0.8–1.0 → Highly relevant, should definitely respond
           - 0.5–0.79 → Relevant, could respond
           - Below 0.5 → Avoid including

        3. Include multiple agents ONLY when the request genuinely spans multiple domains.

        4. Prefer precision over over-routing.

        --------------------------------------------------
        CONTEXTUAL CONTINUITY HEURISTIC

        If the message:

        - Is short
        - Is a follow-up
        - Continues a previous technical thread
        - Refers implicitly to earlier discussion
        - Does not introduce a new domain

        Then increase the probability that it is intended for the agent who responded previously.

        If no new domain signals are detected,
        boost previous agent confidence by +0.15 to +0.25 (capped at 1.0).

        --------------------------------------------------
        EXPLICIT MENTIONS RULE

        If the user explicitly mentions an agent using:

        @agent_name

        Then:

        - That agent must be included.
        - Assign minimum confidence of 0.85.
        - Other agents may be included only if strongly relevant.

        Explicit mention overrides contextual inference.

        --------------------------------------------------
        DOMAIN MATCHING LOGIC

        Evaluate:

        - Architectural topics → Architect
        - Database structure, performance, indexing → DBA
        - Implementation details, debugging, refactoring → Technical
        - Product scope, priorities, feature decisions → Product
        - Cross-domain coordination or ambiguity → Moderator reasoning

        Weigh:

        - Technical keywords
        - Strategic keywords
        - Operational vs conceptual tone
        - Scope breadth

        --------------------------------------------------
        AMBIGUITY HANDLING

        If the message is ambiguous:

        - Select the most likely agent based on domain signals.
        - Assign confidence between 0.55–0.7.
        - Explain briefly in reasoning.

        Never return empty agents list.

        --------------------------------------------------
        STRICT OUTPUT RULE

        Return ONLY valid JSON.
        No markdown.
        No commentary.
        No extra text.
        No trailing characters.
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

        return json_decode($cleaned, true);
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
