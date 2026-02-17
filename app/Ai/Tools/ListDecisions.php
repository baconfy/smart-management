<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Enums\DecisionStatus;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class ListDecisions implements Tool
{
    /**
     * Initialize the constructor with a Project instance.
     */
    public function __construct(private Project $project) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'List architectural decisions recorded for the project. Use this to review past decisions before making new ones, to avoid contradictions or redundancy.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = $this->project->decisions();
        $status = $request['status'] ?? null;

        if ($status && DecisionStatus::tryFrom($status)) {
            $query->where('status', $status);
        }

        $decisions = $query->latest()->get();

        if ($decisions->isEmpty()) {
            return 'No decisions recorded yet for this project.';
        }

        return $decisions->map(fn ($d) => implode("\n", [
            "- [{$d->status->value}] {$d->title}",
            "  Choice: {$d->choice}",
            "  Reasoning: {$d->reasoning}",
        ]))->implode("\n\n");
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Filter by status: active, superseded, or deprecated. Returns all if omitted.'),
        ];
    }
}
