<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BusinessRuleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessRule extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Define the attribute type casting for the model.
     */
    protected function casts(): array
    {
        return [
            'status' => BusinessRuleStatus::class,
        ];
    }

    /**
     * Define an inverse one-to-many relationship with the Project model.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope a query to only include active records with a status of Active.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BusinessRuleStatus::Active);
    }
}
