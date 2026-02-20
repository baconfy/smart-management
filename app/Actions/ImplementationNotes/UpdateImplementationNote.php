<?php

declare(strict_types=1);

namespace App\Actions\ImplementationNotes;

use App\Models\ImplementationNote;

readonly class UpdateImplementationNote
{
    /**
     * Update the given implementation note with the provided data.
     */
    public function __invoke(ImplementationNote $note, array $data): ImplementationNote
    {
        $note->update($data);

        return $note;
    }
}
