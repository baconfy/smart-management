<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Models\Task;

readonly class UpdateTask
{
    /**
     * Update the given task with the provided data.
     */
    public function __invoke(Task $task, array $data): Task
    {
        $task->update($data);

        return $task;
    }
}
