<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Enums\BusinessRuleStatus;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class ListBusinessRules implements Tool
{
    /**
     * Constructor method for the Lucius application.
     *
     * @param  Project  $project  Instance of the Project class.
     */
    public function __construct(private Project $project) {}

    /**
     * Provides a description of the business rules for the project.
     *
     * @return Stringable|string Description of the business rules, optionally filtered by status or category.
     */
    public function description(): Stringable|string
    {
        return 'List business rules for the project, optionally filtered by status or category.';
    }

    /**
     * Handles the incoming request to fetch and filter business rules for the project.
     *
     * @param  Request  $request  The HTTP request instance containing query parameters.
     * @return Stringable|string A formatted string of business rules or a message if none are found.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = $this->project->businessRules();

        if ($status = $request['status'] ?? null) {
            $query->where('status', BusinessRuleStatus::from($status));
        }

        if ($category = $request['category'] ?? null) {
            $query->where('category', $category);
        }

        $rules = $query->latest()->get();

        if ($rules->isEmpty()) {
            return 'No business rules found for this project.';
        }

        return $rules->map(fn ($r) => "- [{$r->status->value}] [{$r->category}] {$r->title} (ID: {$r->id}): {$r->description}")->implode("\n");
    }

    /**
     * Defines the schema for the Lucius application.
     *
     * @param  JsonSchema  $schema  Instance of the JsonSchema class.
     * @return array Returns an array defining the schema structure with descriptions.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Filter by status: active or deprecated.'),
            'category' => $schema->string()->description('Filter by category.'),
        ];
    }
}
