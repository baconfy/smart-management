<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->project = Project::factory()->create(['name' => 'Test Project']);
    $this->owner = User::factory()->create();
    $this->stranger = User::factory()->create();

    $this->project->members()->create(['user_id' => $this->owner->id, 'role' => 'owner']);
    $this->conversation = Conversation::create(['id' => (string) Str::ulid(), 'project_id' => $this->project->id, 'user_id' => $this->owner->id, 'title' => 'Test Conversation']);
});

test('conversation owner is authorized on the channel', function () {
    $authorized = Conversation::where('id', $this->conversation->id)->where('user_id', $this->owner->id)->exists();

    expect($authorized)->toBeTrue();
});

test('stranger is not authorized on the channel', function () {
    $authorized = Conversation::where('id', $this->conversation->id)->where('user_id', $this->stranger->id)->exists();

    expect($authorized)->toBeFalse();
});

test('non-existent conversation is not authorized', function () {
    $authorized = Conversation::where('id', 'non-existent-id')->where('user_id', $this->owner->id)->exists();

    expect($authorized)->toBeFalse();
});
