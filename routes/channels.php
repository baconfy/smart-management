<?php

use App\Models\Project;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('project.{projectId}.chat', function ($user, int $projectId) {
    return Project::whereHas('members', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->where('id', $projectId)->exists();
});
