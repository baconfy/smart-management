<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    /**
     * Establishes a has-many-through relationship to the User model via the ProjectMember model.
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, ProjectMember::class, 'project_id', 'id', 'id', 'user_id');
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
     * Defines a one-to-many relationship with the Task model.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
