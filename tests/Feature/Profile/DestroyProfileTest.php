<?php

declare(strict_types=1);

use App\Models\User;

test('user can delete their account with valid password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'current_password' => 'password',
        ])
        ->assertRedirect('/');

    $this->assertGuest();
    expect(User::find($user->id))->toBeNull();
});

test('user cannot delete account with wrong password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'current_password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('current_password');

    expect(User::find($user->id))->not->toBeNull();
});

test('guests cannot delete account', function () {
    $this->delete(route('profile.destroy'), [
        'current_password' => 'password',
    ])->assertRedirect(route('login'));
});
