<?php

declare(strict_types=1);

namespace App\Actions\ProjectAgents;

use App\Models\ProjectAgent;

readonly class DeleteProjectAgent
{
    /**
     * Delete the given project agent.
     */
    public function __invoke(ProjectAgent $agent): bool
    {
        return $agent->delete();
    }
}
