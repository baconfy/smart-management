<?php

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('project.{project}.chat', function (User $user, int $project) {
    return Project::whereHas('members', fn ($query) => $query->whereUserId($user->id))->whereId($project)->exists();
});

Broadcast::channel('conversation.{conversation}', function (User $user, string $conversation) {
    return Conversation::whereId($conversation)->whereUserId($user->id)->exists();
});
