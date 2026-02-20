<?php

declare(strict_types=1);

namespace App\Actions\BusinessRules;

use App\Models\BusinessRule;
use App\Models\Project;

readonly class CreateBusinessRule
{
    /**
     * Create a new business rule for the given project.
     */
    public function __invoke(Project $project, array $data): BusinessRule
    {
        return $project->businessRules()->create($data);
    }
}
