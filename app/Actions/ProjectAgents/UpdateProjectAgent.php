<?php

declare(strict_types=1);

namespace App\Actions\ProjectAgents;

use App\Models\ProjectAgent;

readonly class UpdateProjectAgent
{
    /**
     * Update the given project agent with the provided data.
     */
    public function __invoke(ProjectAgent $agent, array $data): ProjectAgent
    {
        $agent->update($data);

        return $agent;
    }
}
