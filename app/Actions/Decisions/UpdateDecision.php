<?php

declare(strict_types=1);

namespace App\Actions\Decisions;

use App\Models\Decision;

readonly class UpdateDecision
{
    /**
     * Update the given decision with the provided data.
     */
    public function __invoke(Decision $decision, array $data): Decision
    {
        $decision->update($data);

        return $decision;
    }
}
