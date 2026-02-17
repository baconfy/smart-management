<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

class AddProjectMember
{
    /**
     * Handle the addition of a new member to the given project.
     */
    public function __invoke(Project $project, User $user, string $role = 'member'): ProjectMember
    {
        return $project->members()->create(['user_id' => $user->id, 'role' => $role]);
    }
}
