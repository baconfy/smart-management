<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;

readonly class UpdateProject
{
    /**
     * Update the given project with the provided data.
     */
    public function __invoke(Project $project, array $data): Project
    {
        $project->update($data);

        return $project;
    }
}
