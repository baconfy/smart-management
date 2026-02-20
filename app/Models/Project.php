<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Project $project) {
            if ($project->isForceDeleting()) {
                $project->members()->forceDelete();
                $project->agents()->forceDelete();
                $project->conversations->each->forceDelete();
                $project->decisions()->forceDelete();
                $project->businessRules()->forceDelete();
                $project->tasks->each->forceDelete();
            } else {
                $project->members()->delete();
                $project->agents()->delete();
                $project->conversations->each->delete();
                $project->decisions()->delete();
                $project->businessRules()->delete();
                $project->tasks->each->delete();
            }
        });

        static::restoring(function (Project $project) {
            $project->members()->onlyTrashed()->restore();
            $project->agents()->onlyTrashed()->restore();
            $project->conversations()->onlyTrashed()->each(function (Conversation $conversation) {
                $conversation->restore();
            });
            $project->decisions()->onlyTrashed()->restore();
            $project->businessRules()->onlyTrashed()->restore();
            $project->tasks()->onlyTrashed()->each(function (Task $task) {
                $task->restore();
            });
        });
    }

    /**
     * Define the attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /**
     * Defines an accessor for retrieving the owner of the model.
     * The owner is determined by finding a related member with the role of 'owner'
     * and accessing the associated User model.
     */
    protected function owner(): Attribute
    {
        return Attribute::get(fn (): ?User => $this->members()->where('role', 'owner')->first()?->user);
    }

    /**
     * Get the members associated with the project.
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Defines a has-many relationship with the ProjectAgent model.
     */
    public function agents(): HasMany
    {
        return $this->hasMany(ProjectAgent::class);
    }

    /**
     * Defines a has-many relationship with the Conversation model.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Establishes a one-to-many relationship with the Decision model.
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    /**
     * Defines a one-to-many relationship with the BusinessRule model.
     */
    public function businessRules(): HasMany
    {
        return $this->hasMany(BusinessRule::class);
    }

    /**
     * Defines a one-to-many relationship with the TaskStatus model.
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class);
    }

    /**
     * Defines a one-to-many relationship with the Task model.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the columns that should receive a unique identifier.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Establishes a has-many-through relationship to the User model via the ProjectMember model.
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, ProjectMember::class, 'project_id', 'id', 'id', 'user_id');
    }

    /**
     * Specifies the model's route key name for implicit route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
