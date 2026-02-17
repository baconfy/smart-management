<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'message' => ['required', 'string', 'max:10000'],
            'agent_ids' => ['required', 'array', 'min:1'],
            'agent_ids.*' => ['integer', "exists:project_agents,id,project_id,{$project->id}"],
            'conversation_id' => ['nullable', 'string', 'exists:agent_conversations,id'],
        ];
    }
}
