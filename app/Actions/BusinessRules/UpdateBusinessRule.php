<?php

declare(strict_types=1);

namespace App\Actions\BusinessRules;

use App\Models\BusinessRule;

readonly class UpdateBusinessRule
{
    /**
     * Update the given business rule with the provided data.
     */
    public function __invoke(BusinessRule $rule, array $data): BusinessRule
    {
        $rule->update($data);

        return $rule;
    }
}
