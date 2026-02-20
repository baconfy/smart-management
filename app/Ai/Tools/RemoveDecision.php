<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\Decisions\DeleteDecision as DeleteDecisionAction;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class RemoveDecision implements Tool
{
    /**
     * Initialize the class with a Project instance.
     */
    public function __construct(private Project $project, private DeleteDecisionAction $deleteDecision) {}

    /**
     * Get the description of the decision removal process.
     */
    public function description(): Stringable|string
    {
        return 'Remove an architectural decision from the project. Use this when a decision is no longer relevant or was recorded by mistake.';
    }

    /**
     * Handle the request to remove a decision associated with the project.
     */
    public function handle(Request $request): Stringable|string
    {
        $decision = $this->project->decisions()->find($request['decision_id']);

        if (! $decision) {
            return 'Decision not found in this project.';
        }

        ($this->deleteDecision)($decision);

        return "Decision removed: \"{$decision->title}\" (ID: {$decision->id})";
    }

    /**
     * Define and return the schema for removing a decision.
     *
     * @param  JsonSchema  $schema  The JSON schema builder instance.
     * @return array The structured schema definition for the decision removal request.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'decision_id' => $schema->integer()->description('The ID of the decision to remove.')->required(),
        ];
    }
}
