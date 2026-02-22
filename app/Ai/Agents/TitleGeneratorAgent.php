<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class TitleGeneratorAgent implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        You generate short, descriptive conversation titles.

        Rules:
        - Maximum 60 characters
        - Return ONLY the title text, nothing else
        - No quotes, no explanation, no punctuation wrapping
        - Be concise and descriptive
        - Capture the main topic or intent of the message
        INSTRUCTIONS;
    }
}
