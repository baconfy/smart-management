<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Enums\AgentType;
use App\Models\Project;

class SeedProjectAgents
{
    /** @var list<array{type: AgentType, name: string, is_system: bool}> */
    private const array DEFAULTS = [
        ['type' => AgentType::Moderator, 'name' => 'Moderator', 'is_system' => true],
        ['type' => AgentType::Architect, 'name' => 'Architect', 'is_system' => false],
        ['type' => AgentType::Analyst, 'name' => 'Analyst', 'is_system' => false],
        ['type' => AgentType::Pm, 'name' => 'PM', 'is_system' => false],
        ['type' => AgentType::Technical, 'name' => 'Technical', 'is_system' => false],
    ];

    /**
     * Handles the creation of default agents for the given project.
     */
    public function __invoke(Project $project): void
    {
        foreach (self::DEFAULTS as $config) {
            $project->agents()->create([
                'type' => $config['type']->value,
                'name' => $config['name'],
                'instructions' => $this->loadInstructions($config['type']),
                'is_system' => $config['is_system'],
                'is_default' => true,
            ]);
        }
    }

    /**
     * Loads the instruction file for the specified agent type.
     *
     * @param  AgentType  $type  The type of agent for which instructions are to be loaded.
     * @return string The contents of the instruction file.
     */
    private function loadInstructions(AgentType $type): string
    {
        return file_get_contents(resource_path("instructions/{$type->value}.md"));
    }
}
