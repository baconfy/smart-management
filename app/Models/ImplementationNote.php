<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImplementationNote extends Model
{
    use SoftDeletes;

    /**
     * Define the model's attribute type casting.
     */
    protected function casts(): array
    {
        return [
            'code_snippets' => 'array',
        ];
    }

    /**
     * Establishes a relationship indicating that this model belongs to a Task.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
