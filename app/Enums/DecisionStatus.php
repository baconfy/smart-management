<?php

declare(strict_types=1);

namespace App\Enums;

enum DecisionStatus: string
{
    case Active = 'active';
    case Superseded = 'superseded';
    case Deprecated = 'deprecated';
}
