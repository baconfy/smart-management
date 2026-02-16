<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Backlog = 'backlog';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Blocked = 'blocked';
}
