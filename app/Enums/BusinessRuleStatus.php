<?php

declare(strict_types=1);

namespace App\Enums;

enum BusinessRuleStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
}
