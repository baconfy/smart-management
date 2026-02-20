<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Conversation $conversation) {
            if ($conversation->isForceDeleting()) {
                $conversation->messages()->forceDelete();
            } else {
                $conversation->messages()->delete();
            }
        });

        static::restoring(function (Conversation $conversation) {
            $conversation->messages()->onlyTrashed()->restore();
        });
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'agent_conversations';

    /**
     * The primary key for the model.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * Establishes a relationship indicating that this model belongs to a Project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Defines a relationship indicating that this model belongs to a User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Defines a relationship indicating that this model has many ConversationMessage instances.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'conversation_id');
    }

    /**
     * Defines a relationship indicating that this model belongs to a Task model.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
