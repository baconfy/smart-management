<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DecisionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    /**
     * Define the attribute type casts for the model.
     */
    protected function casts(): array
    {
        return [
            'status' => DecisionStatus::class,
            'alternatives_considered' => 'array',
        ];
    }

    /**
     * Defines the relationship between the current model and the Project model.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Adds a query scope to filter results by active status.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', DecisionStatus::Active);
    }
}
