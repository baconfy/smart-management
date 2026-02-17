<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Models\Project;
use App\Models\ProjectAgent;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class ArchitectAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    /**
     * Initialize a new instance of the class with the specified ProjectAgent dependency.
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
        return [];
    }

    /**
     * Get the project this agent belongs to.
     */
    public function project(): Project
    {
        return $this->projectAgent->project;
    }
}
