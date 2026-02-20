<?php

declare(strict_types=1);

namespace App\Actions\ImplementationNotes;

use App\Models\ImplementationNote;
use App\Models\Task;

readonly class CreateImplementationNote
{
    /**
     * Create a new implementation note for the given task.
     */
    public function __invoke(Task $task, array $data): ImplementationNote
    {
        return $task->implementationNotes()->create($data);
    }
}
