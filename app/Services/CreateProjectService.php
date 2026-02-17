<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Projects\AddProjectMember;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\SeedProjectAgents;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateProjectService
{
    public function __construct(private CreateProject $createProject, private AddProjectMember $addProjectMember, private SeedProjectAgents $seedProjectAgents) {}

    public function __invoke(User $owner, array $data): Project
    {
        return DB::transaction(function () use ($owner, $data) {
            $project = ($this->createProject)($data);

            ($this->addProjectMember)($project, $owner, 'owner');
            ($this->seedProjectAgents)($project);

            return $project;
        });
    }
}
