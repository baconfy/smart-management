<?php

declare(strict_types=1);

namespace App\Actions\Decisions;

use App\Models\Decision;
use App\Models\Project;

readonly class CreateDecision
{
    /**
     * Create a new decision for the given project.
     */
    public function __invoke(Project $project, array $data): Decision
    {
        return $project->decisions()->create($data);
    }
}
