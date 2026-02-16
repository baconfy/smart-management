<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentType: string
{
    case Moderator = 'moderator';
    case Architect = 'architect';
    case Analyst = 'analyst';
    case Pm = 'pm';
    case Technical = 'technical';
    case Custom = 'custom';
}
