<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Support\Collection;

class SeedProjectStatuses
{
    /**
     * Seed default task statuses for the given project.
     */
    public function __invoke(Project $project): void
    {
        $this->defaults()->each(fn ($config) => $project->statuses()->create($config));
    }

    /**
     * Generate a collection of default configurations based on the given type.
     */
    private function defaults(): Collection
    {
        return collect([
            [
                'name' => __('To Do'),
                'slug' => 'todo',
                'color' => '#71717a',
                'position' => 0,
                'is_default' => true,
                'is_closed' => false,
            ],
            [
                'name' => __('In Progress'),
                'slug' => 'in_progress',
                'color' => '#3b82f6',
                'position' => 1,
                'is_default' => false,
                'is_in_progress' => true,
                'is_closed' => false,
            ],
            [
                'name' => __('Done'),
                'slug' => 'done',
                'color' => '#22c55e',
                'position' => 2,
                'is_default' => false,
                'is_closed' => true,
            ],
        ]);
    }
}
