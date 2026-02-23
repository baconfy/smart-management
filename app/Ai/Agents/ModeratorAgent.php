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
        You are a message router. Your job is to analyze the user's message and decide which agent(s) should respond.

        Available agents:
        {$agentList}

        Respond ONLY with a JSON object (no markdown, no backticks):
        {
            "agents": [
                { "type": "agent_type", "confidence": 0.0-1.0 }
            ],
            "reasoning": "Brief explanation of why these agents were chosen."
        }

        Rules:
        - confidence 0.8-1.0 = highly relevant, should definitely respond
        - confidence 0.5-0.79 = somewhat relevant, could respond
        - confidence below 0.5 = not relevant
        - Include multiple agents only when the question genuinely spans multiple domains
        - Always include at least one agent
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
