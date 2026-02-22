<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgentRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'model' => ['nullable', 'string', 'max:255'],
            'tools' => ['nullable', 'array'],
            'tools.*' => ['string'],
        ];
    }
}
