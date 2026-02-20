<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Task $task) {
            if ($task->isForceDeleting()) {
                $task->subtasks->each->forceDelete();
                $task->implementationNotes()->forceDelete();
            } else {
                $task->subtasks->each->delete();
                $task->implementationNotes()->delete();
            }
        });

        static::restoring(function (Task $task) {
            $task->subtasks()->onlyTrashed()->get()->each->restore();
            $task->implementationNotes()->onlyTrashed()->restore();
        });
    }

    /**
     * Define the castable attributes for the model.
     */
    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'sort_order' => 'integer',
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
     * Define the relationship with the task status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }

    /**
     * Define an inverse one-to-one or many relationship with the same model, referencing the 'parent_task_id' column.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    /**
     * Define a one-to-many relationship with the same model for subtasks.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id');
    }

    /**
     * Define a one-to-many relationship with the ImplementationNote model.
     */
    public function implementationNotes(): HasMany
    {
        return $this->hasMany(ImplementationNote::class);
    }

    /**
     * Get the columns that should receive a unique identifier.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Get the route key name for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Scope a query to filter tasks by status slug.
     */
    public function scopeWithStatus(Builder $query, string $slug): Builder
    {
        return $query->whereHas('status', fn (Builder $q) => $q->where('slug', $slug));
    }

    /**
     * Scope a query to filter tasks in closed statuses.
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $q) => $q->where('is_closed', true));
    }

    /**
     * Scope a query to filter tasks in open statuses.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $q) => $q->where('is_closed', false));
    }

    /**
     * Establishes a one-to-one relationship with the Conversation model.
     */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }
}
