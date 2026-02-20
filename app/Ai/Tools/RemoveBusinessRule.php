<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\BusinessRules\DeleteBusinessRule as DeleteBusinessRuleAction;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class RemoveBusinessRule implements Tool
{
    /**
     * Initialize the class with a Project instance.
     */
    public function __construct(private Project $project, private DeleteBusinessRuleAction $deleteBusinessRule) {}

    /**
     * Get the description of the business rule removal process.
     */
    public function description(): Stringable|string
    {
        return 'Remove a business rule from the project. Use this when a rule is no longer applicable or was recorded by mistake.';
    }

    /**
     * Handle the request to remove a business rule associated with the project.
     */
    public function handle(Request $request): Stringable|string
    {
        $rule = $this->project->businessRules()->find($request['business_rule_id']);

        if (! $rule) {
            return 'Business rule not found in this project.';
        }

        ($this->deleteBusinessRule)($rule);

        return "Business rule removed: \"{$rule->title}\" (ID: {$rule->id})";
    }

    /**
     * Define and return the schema for removing a business rule.
     *
     * @param  JsonSchema  $schema  The JSON schema builder instance.
     * @return array The structured schema definition for the business rule removal request.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'business_rule_id' => $schema->integer()->description('The ID of the business rule to remove.')->required(),
        ];
    }
}
