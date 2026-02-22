<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StreamAgentsRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'agent_ids' => ['required', 'array', 'min:1'],
            'agent_ids.*' => ['required', 'integer', "exists:project_agents,id,project_id,{$project->id}"],
        ];
    }
}
