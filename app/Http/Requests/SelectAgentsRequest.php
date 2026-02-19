<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectAgentsRequest extends FormRequest
{
    /**
     * Define validation rules for the given request.
     *
     * @return array The validation rules for the request data.
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'agent_ids' => ['required', 'array', 'min:1'],
            'agent_ids.*' => ['required', 'integer', "exists:project_agents,id,project_id,{$project->id}"],
            'message' => ['required', 'string', 'max:10000'],
        ];
    }
}
