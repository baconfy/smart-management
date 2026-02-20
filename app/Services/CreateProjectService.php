<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Projects\AddProjectMember;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\SeedProjectAgents;
use App\Actions\Projects\SeedProjectStatuses;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class CreateProjectService
{
    /**
     * Handles the initialization of dependencies required for project creation, member addition, and agent seeding.
     */
    public function __construct(
        private CreateProject $createProject,
        private AddProjectMember $addProjectMember,
        private SeedProjectAgents $seedProjectAgents,
        private SeedProjectStatuses $seedProjectStatuses,
    ) {}

    /**
     * Handles the invocation of the class, creating a project, and associating the provided user as the owner.
     *
     * This method performs the operation within a database transaction to ensure
     * consistency. It creates a new project, assigns the specified user as the project
     * owner, and seeds the project with default agents.
     *
     * @param  User  $owner  The user to be assigned as the owner of the project.
     * @param  array  $data  The data to be used for creating the project.
     * @return Project The newly created project instance.
     *
     * @throws Throwable
     */
    public function __invoke(User $owner, array $data): Project
    {
        return DB::transaction(function () use ($owner, $data) {
            $project = ($this->createProject)($data);

            ($this->addProjectMember)($project, $owner, 'owner');
            ($this->seedProjectStatuses)($project);
            ($this->seedProjectAgents)($project);

            return $project;
        });
    }
}
