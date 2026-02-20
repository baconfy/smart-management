<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Models\Project;
use App\Models\Task;

readonly class CreateTask
{
    /**
     * Create a new task for the given project.
     */
    public function __invoke(Project $project, array $data): Task
    {
        return $project->tasks()->create($data);
    }
}
