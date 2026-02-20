<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\Decisions\CreateDecision as CreateDecisionAction;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class CreateDecision implements Tool
{
    /**
     * Initialize a new instance of the class.
     */
    public function __construct(private Project $project, private CreateDecisionAction $createDecision) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Record an architectural decision for the project. Use this when a technical choice has been made that should be documented for future reference.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $decision = ($this->createDecision)($this->project, [
            'title' => $request['title'],
            'choice' => $request['choice'],
            'reasoning' => $request['reasoning'],
            'alternatives_considered' => $request['alternatives_considered'] ?? null,
            'context' => $request['context'] ?? null,
        ]);

        return "Decision recorded: \"{$decision->title}\" (ID: {$decision->id})";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Short title for the decision (e.g. "Use PostgreSQL").')->required(),
            'choice' => $schema->string()->description('The chosen option.')->required(),
            'reasoning' => $schema->string()->description('Why this choice was made.')->required(),
            'alternatives_considered' => $schema->array()->items($schema->string())->description('List of alternatives that were considered.'),
            'context' => $schema->string()->description('Additional context or constraints that influenced the decision.'),
        ];
    }
}
