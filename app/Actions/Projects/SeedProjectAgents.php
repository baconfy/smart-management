<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Enums\AgentType;
use App\Models\Project;

class SeedProjectAgents
{
    /** @var list<array{type: AgentType, name: string, tools: list<string>}> */
    private const array DEFAULTS = [
        [
            'type' => AgentType::Architect,
            'name' => 'Architect',
            'tools' => ['CreateDecision', 'ListDecisions', 'UpdateDecision', 'ListBusinessRules'],
        ],
        [
            'type' => AgentType::Analyst,
            'name' => 'Analyst',
            'tools' => ['ListDecisions', 'CreateBusinessRule', 'ListBusinessRules', 'UpdateBusinessRule'],
        ],
        [
            'type' => AgentType::Pm,
            'name' => 'PM',
            'tools' => ['ListDecisions', 'ListBusinessRules', 'CreateTask', 'ListTasks', 'UpdateTask'],
        ],
        [
            'type' => AgentType::Dba,
            'name' => 'DBA',
            'tools' => ['ListDecisions', 'ListBusinessRules', 'ListTasks'],
        ],
        [
            'type' => AgentType::Technical,
            'name' => 'Technical',
            'tools' => ['ListDecisions', 'ListBusinessRules', 'ListTasks', 'UpdateTask', 'CreateImplementationNote', 'ListImplementationNotes', 'UpdateImplementationNote'],
        ],
    ];

    /**
     * Handles the initialization of default agent configurations for a given project.
     *
     * Iterates through a predefined set of default configurations and creates
     * agents associated with the provided project. Each agent is initialized
     * with specific attributes, including type, name, instructions, tools, and
     * a flag indicating it is a default configuration.
     *
     * @param  Project  $project  The project for which default agents are being created.
     */
    public function __invoke(Project $project): void
    {
        foreach (self::DEFAULTS as $config) {
            $project->agents()->create([
                'type' => $config['type']->value,
                'name' => $config['name'],
                'instructions' => $this->loadInstructions($config['type']),
                'tools' => $config['tools'],
                'is_default' => true,
            ]);
        }
    }

    /**
     * Loads the instructions for a given agent type from a resource file.
     *
     * @param  AgentType  $type  The type of agent for which the instructions are being loaded.
     * @return string The contents of the instructions file.
     */
    private function loadInstructions(AgentType $type): string
    {
        return file_get_contents(resource_path("instructions/{$type->value}.md"));
    }
}
