<?php

declare(strict_types=1);

namespace App\Actions\BusinessRules;

use App\Models\BusinessRule;

readonly class DeleteBusinessRule
{
    /**
     * Delete the given business rule.
     */
    public function __invoke(BusinessRule $rule): bool
    {
        return $rule->delete();
    }
}
