<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectStatus extends Model
{
    use HasFactory;

    /**
     * Define the castable attributes for the model.
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * Define the inverse relationship with the Project model.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Define the one-to-many relationship with tasks.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope to get only the default status for a project.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get statuses that represent closed/completed.
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('is_closed', true);
    }

    /**
     * Scope to order by position.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }
}
