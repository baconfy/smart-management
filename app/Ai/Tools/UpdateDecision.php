<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class UpdateDecision implements Tool
{
    /**
     * Initialize the class with a Project instance.
     *
     * @param  Project  $project  The project instance.
     */
    public function __construct(private Project $project) {}

    /**
     * Get the description of the architectural decision update process.
     *
     * @return Stringable|string A summary explaining the purpose of updating an existing architectural decision,
     *                           including details such as the title, choice, reasoning, status, or related fields.
     */
    public function description(): Stringable|string
    {
        return 'Update an existing architectural decision. Use this to change the title, choice, reasoning, status, or other fields of a previously recorded decision.';
    }

    /**
     * Handle the request to update a decision associated with the project.
     *
     * This method retrieves a decision by its ID within the project, updates it with
     * the provided fields from the request, and returns a success message. If the
     * decision cannot be found, an appropriate error message is returned.
     *
     * @param  Request  $request  The incoming HTTP request containing decision data.
     * @return Stringable|string A success message with the updated decision details
     *                           or an error message if the decision is not found.
     */
    public function handle(Request $request): Stringable|string
    {
        $decision = $this->project->decisions()->find($request['decision_id']);

        if (! $decision) {
            return 'Decision not found in this project.';
        }

        $fields = array_filter([
            'title' => $request['title'] ?? null,
            'choice' => $request['choice'] ?? null,
            'reasoning' => $request['reasoning'] ?? null,
            'status' => $request['status'] ?? null,
            'alternatives_considered' => $request['alternatives_considered'] ?? null,
            'context' => $request['context'] ?? null,
        ], fn ($value) => $value !== null);

        $decision->update($fields);

        return "Decision updated: \"{$decision->title}\" (ID: {$decision->id})";
    }

    /**
     * Define and return the schema for updating a decision.
     *
     * @param  JsonSchema  $schema  The JSON schema builder instance.
     * @return array The structured schema definition for the decision update request.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'decision_id' => $schema->integer()->description('The ID of the decision to update.')->required(),
            'title' => $schema->string()->description('New title for the decision.'),
            'choice' => $schema->string()->description('New chosen option.'),
            'reasoning' => $schema->string()->description('New reasoning.'),
            'status' => $schema->string()->description('New status: active, superseded, or deprecated.'),
            'alternatives_considered' => $schema->array()->items($schema->string())->description('Updated list of alternatives.'),
            'context' => $schema->string()->description('Updated context.'),
        ];
    }
}
