<?php

declare(strict_types=1);

namespace App\Actions\ProjectAgents;

use App\Models\Project;
use App\Models\ProjectAgent;

readonly class CreateProjectAgent
{
    /**
     * Create a new agent for the given project.
     */
    public function __invoke(Project $project, array $data): ProjectAgent
    {
        return $project->agents()->create($data);
    }
}
