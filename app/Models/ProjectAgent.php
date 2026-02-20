<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectAgent extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Define the attributes that should be type casted.
     */
    protected function casts(): array
    {
        return [
            'type' => AgentType::class,
            'is_default' => 'boolean',
            'settings' => 'array',
            'tools' => 'array',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship with the Project model.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Only pre-defined agents (not custom).
     */
    public function scopeDefaults(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
