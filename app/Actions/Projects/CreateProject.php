<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;

class CreateProject
{
    /**
     * Handles the invocation of the method to create a new
     * Project instance using the provided data.
     */
    public function __invoke(array $data): Project
    {
        return Project::query()->create($data);
    }
}
