<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\BusinessRules\CreateBusinessRule as CreateBusinessRuleAction;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class CreateBusinessRule implements Tool
{
    /**
     * Construct a new instance of the class with the provided Project dependency.
     */
    public function __construct(private Project $project, private CreateBusinessRuleAction $createBusinessRule) {}

    /**
     * Get the description of the purpose for recording a business rule.
     */
    public function description(): Stringable|string
    {
        return 'Record a business rule for the project. Use this when a domain rule, policy, or constraint has been identified that should be documented.';
    }

    /**
     * Handle the incoming request to create a new business rule for the project.
     */
    public function handle(Request $request): Stringable|string
    {
        $rule = ($this->createBusinessRule)($this->project, [
            'title' => $request['title'],
            'description' => $request['description'],
            'category' => $request['category'],
            'status' => $request['status'],
        ]);

        return "Business rule recorded: \"{$rule->title}\" (ID: {$rule->id})";
    }

    /**
     * Define the JSON schema for the rule configuration.
     *
     * @param  JsonSchema  $schema  The schema instance for specifying the JSON structure.
     * @return array The defined schema with required fields including title, description, and category.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Short title for the rule.')->required(),
            'description' => $schema->string()->description('Full description of the business rule.')->required(),
            'category' => $schema->string()->description('Category (e.g. billing, security, compliance, operations).')->required(),
            'status' => $schema->string()->description('Status (e.g. active, ideia, deprecated, etc).')->required(),
        ];
    }
}
