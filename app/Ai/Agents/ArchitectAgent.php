<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\CreateDecision;
use App\Ai\Tools\ListDecisions;
use App\Concerns\ReadsConversationHistory;
use App\Models\Project;
use App\Models\ProjectAgent;
use Laravel\Ai\Attributes\UseSmartestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

#[UseSmartestModel]
class ArchitectAgent implements Agent, Conversational, HasTools
{
    use Promptable, ReadsConversationHistory, RemembersConversations;

    /**
     * Initialize the constructor with the provided ProjectAgent instance.
     */
    public function __construct(public readonly ProjectAgent $projectAgent) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $project = $this->project();

        $context = "\n\n## Project Context\n- Name: {$project->name}";

        if ($project->description) {
            $context .= "\n- Description: {$project->description}";
        }

        return $this->projectAgent->instructions.$context;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return array<Tool>
     */
    public function tools(): iterable
    {
        $project = $this->project();

        return [
            new CreateDecision($project),
            new ListDecisions($project),
        ];
    }

    /**
     * Get the project this agent belongs to.
     */
    public function project(): Project
    {
        return $this->projectAgent->project;
    }
}
