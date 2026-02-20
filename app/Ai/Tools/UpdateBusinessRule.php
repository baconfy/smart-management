<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\BusinessRules\UpdateBusinessRule as UpdateBusinessRuleAction;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class UpdateBusinessRule implements Tool
{
    /**
     * Initializes a new instance of the class with the specified project.
     */
    public function __construct(private Project $project, private UpdateBusinessRuleAction $updateBusinessRule) {}

    /**
     * Provides a description of the business rule update functionality.
     */
    public function description(): Stringable|string
    {
        return 'Update an existing business rule. Use this to change the title, description, category, or status.';
    }

    /**
     * Handles the incoming request to update a business rule for the project.
     */
    public function handle(Request $request): Stringable|string
    {
        $rule = $this->project->businessRules()->find($request['business_rule_id']);

        if (! $rule) {
            return 'Business rule not found in this project.';
        }

        $fields = array_filter([
            'title' => $request['title'] ?? null,
            'description' => $request['description'] ?? null,
            'category' => $request['category'] ?? null,
            'status' => $request['status'] ?? null,
        ], fn ($value) => $value !== null);

        ($this->updateBusinessRule)($rule, $fields);

        return "Business rule updated: \"{$rule->title}\" (ID: {$rule->id})";
    }

    /**
     * Defines the schema structure for the business rule update operation.
     *
     * @param  JsonSchema  $schema  The JSON schema instance used to define the structure.
     * @return array The defined schema for the business rule update, including field descriptions and requirements.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'business_rule_id' => $schema->integer()->description('The ID of the business rule to update.')->required(),
            'title' => $schema->string()->description('New title.'),
            'description' => $schema->string()->description('New description.'),
            'category' => $schema->string()->description('New category.'),
            'status' => $schema->string()->description('New status: active or deprecated.'),
        ];
    }
}
