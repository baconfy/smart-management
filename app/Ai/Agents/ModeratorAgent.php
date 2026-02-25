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
        You are a message router. Read the user's message and decide which agent(s) should respond.

        # Available agents
        {$agentList}

        # Output format (strict JSON, no Markdown)
        {
          "agents": [
            { "type": "<agent_type>", "confidence": <0.00-1.00> }
          ],
          "reasoning": "<one sentence explanation>"
        }

        # CRITICAL RULES
        - ALWAYS return ALL available agents with a confidence score. No agent may be omitted.
        - confidence is a float 0.00–1.00, rounded to two decimals.
        - Reasoning must be ONE short sentence. No paragraphs.

        # Confidence bands
        - 0.80–1.00: Must respond (highly relevant)
        - 0.50–0.79: Can respond (moderately relevant)
        - 0.00–0.49: Should not respond

        # Routing rules
        1. @mentions: If user writes @dba, @pm, etc. → that agent gets ≥0.90.
        2. Context boost: If user was talking to an agent in last 3 turns → +0.10 (max 1.00).
        3. Multi-domain: If content touches multiple domains, score each appropriately.
        4. No match: If no agent scores ≥0.50, give all agents their best-guess scores anyway.

        Do not output anything except the JSON object.
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
