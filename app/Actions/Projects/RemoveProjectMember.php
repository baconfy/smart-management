<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\ProjectMember;

readonly class RemoveProjectMember
{
    /**
     * Remove the given project member.
     */
    public function __invoke(ProjectMember $member): bool
    {
        return $member->delete();
    }
}
