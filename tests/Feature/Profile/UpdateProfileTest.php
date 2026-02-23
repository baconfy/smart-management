<?php

declare(strict_types=1);

use App\Models\User;

test('user can update their name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-profile-information.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
        ])
        ->assertRedirect();

    expect($user->fresh()->name)->toBe('Updated Name');
});

test('user can update their email', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->put(route('user-profile-information.update'), [
            'name' => $user->name,
            'email' => 'newemail@example.com',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->email)->toBe('newemail@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-profile-information.update'), [
            'name' => '',
            'email' => $user->email,
        ])
        ->assertSessionHasErrors('name', errorBag: 'updateProfileInformation');
});

test('email must be valid', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-profile-information.update'), [
            'name' => $user->name,
            'email' => 'not-an-email',
        ])
        ->assertSessionHasErrors('email', errorBag: 'updateProfileInformation');
});

test('email must be unique', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-profile-information.update'), [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ])
        ->assertSessionHasErrors('email', errorBag: 'updateProfileInformation');
});

test('guests cannot update profile', function () {
    $this->put(route('user-profile-information.update'), [
        'name' => 'Test',
        'email' => 'test@example.com',
    ])->assertRedirect(route('login'));
});
