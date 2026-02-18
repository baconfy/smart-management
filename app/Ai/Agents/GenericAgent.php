<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\CreateBusinessRule;
use App\Ai\Tools\CreateImplementationNote;
use App\Ai\Tools\CreateTask;
use App\Ai\Tools\ListBusinessRules;
use App\Ai\Tools\ListDecisions;
use App\Ai\Tools\ListImplementationNotes;
use App\Ai\Tools\ListTasks;
use App\Ai\Tools\UpdateBusinessRule;
use App\Ai\Tools\UpdateImplementationNote;
use App\Ai\Tools\UpdateTask;
use App\Concerns\ReadsConversationHistory;
use App\Enums\AgentType;
use App\Models\Project;
use App\Models\ProjectAgent;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class GenericAgent implements Agent, Conversational, HasTools
{
    use Promptable, ReadsConversationHistory, RemembersConversations;

    public function __construct(public readonly ProjectAgent $projectAgent) {}

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
     * @return array<Tool>
     */
    public function tools(): iterable
    {
        $project = $this->project();

        return match ($this->projectAgent->type) {
            AgentType::Analyst => [
                new ListDecisions($project),
                new CreateBusinessRule($project),
                new ListBusinessRules($project),
                new UpdateBusinessRule($project),
            ],
            AgentType::Pm => [
                new ListDecisions($project),
                new ListBusinessRules($project),
                new CreateTask($project),
                new ListTasks($project),
                new UpdateTask($project),
            ],
            AgentType::Technical => [
                new ListDecisions($project),
                new ListBusinessRules($project),
                new ListTasks($project),
                new UpdateTask($project),
                new CreateImplementationNote($project),
                new ListImplementationNotes($project),
                new UpdateImplementationNote($project),
            ],
            default => [],
        };
    }

    public function project(): Project
    {
        return $this->projectAgent->project;
    }
}
