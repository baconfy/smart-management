<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    /**
     * Define an inverse one-to-one or many relationship with the Project model.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Define an inverse one-to-one or many relationship with the User model.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
