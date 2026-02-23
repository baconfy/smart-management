<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user can update their password', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'current-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('current password must be correct', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password', errorBag: 'updatePassword');
});

test('new password must be confirmed', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'current-password',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ])
        ->assertSessionHasErrors('password', errorBag: 'updatePassword');
});

test('current password is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => '',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password', errorBag: 'updatePassword');
});

test('new password is required', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'current-password',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertSessionHasErrors('password', errorBag: 'updatePassword');
});

test('guests cannot update password', function () {
    $this->put(route('user-password.update'), [
        'current_password' => 'current-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('login'));
});

test('password page renders for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.password'))
        ->assertOk();
});
