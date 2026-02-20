<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Models\Task;

readonly class DeleteTask
{
    /**
     * Delete the given task.
     */
    public function __invoke(Task $task): bool
    {
        return $task->delete();
    }
}
