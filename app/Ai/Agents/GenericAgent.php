<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Concerns\ReadsConversationHistory;
use App\Models\Project;
use App\Models\ProjectAgent;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class GenericAgent implements Agent, Conversational, HasTools
{
    use Promptable, ReadsConversationHistory, RemembersConversations;

    /** @var array<int, mixed>|null */
    private ?array $cachedTools = null;

    /**
     * Initialize the Lucius application with the given ProjectAgent instance.
     *
     * @param  ProjectAgent  $projectAgent  The project agent instance
     */
    public function __construct(public readonly ProjectAgent $projectAgent) {}

    /**
     * Generate the instructions for the current project, including its context.
     *
     * @return Stringable|string The project instructions with additional context details
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
     * Retrieve a list of tool instances for the current project.
     *
     * @return iterable The collection of tool instances
     */
    public function tools(): iterable
    {
        if ($this->cachedTools !== null) {
            return $this->cachedTools;
        }

        $tools = $this->projectAgent->tools ?? [];
        $project = $this->project();

        return $this->cachedTools = collect($tools)->map(function (string $name) use ($project) {
            return app()->make("App\\Ai\\Tools\\{$name}", ['project' => $project]);
        })->all();
    }

    /**
     * Retrieve the associated project from the project agent.
     */
    public function project(): Project
    {
        return $this->projectAgent->project;
    }
}
