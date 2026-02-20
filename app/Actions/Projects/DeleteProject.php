<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;

readonly class DeleteProject
{
    /**
     * Delete the given project.
     */
    public function __invoke(Project $project): bool
    {
        return $project->delete();
    }
}
