<?php

declare(strict_types=1);

namespace App\Actions\ImplementationNotes;

use App\Models\ImplementationNote;

readonly class DeleteImplementationNote
{
    /**
     * Delete the given implementation note.
     */
    public function __invoke(ImplementationNote $note): bool
    {
        return $note->delete();
    }
}
