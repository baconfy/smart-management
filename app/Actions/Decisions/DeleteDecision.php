<?php

declare(strict_types=1);

namespace App\Actions\Decisions;

use App\Models\Decision;

readonly class DeleteDecision
{
    /**
     * Delete the given decision.
     */
    public function __invoke(Decision $decision): bool
    {
        return $decision->delete();
    }
}
