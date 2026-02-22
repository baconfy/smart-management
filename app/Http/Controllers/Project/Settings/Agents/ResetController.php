<?php

declare(strict_types=1);

namespace App\Http\Controllers\Project\Settings\Agents;

use App\Actions\ProjectAgents\UpdateProjectAgent;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResetController extends Controller
{
    public function __invoke(Request $request, Project $project, ProjectAgent $agent, UpdateProjectAgent $updateProjectAgent): RedirectResponse
    {
        $this->authorize('view', $project);

        abort_unless($agent->is_default, 403);

        $instructions = file_get_contents(resource_path("instructions/{$agent->type->value}.md"));
        $name = str_replace('# ', '', strtok($instructions, "\n"));

        $updateProjectAgent($agent, [
            'name' => $name,
            'model' => null,
            'instructions' => $instructions,
        ]);

        return back();
    }
}
