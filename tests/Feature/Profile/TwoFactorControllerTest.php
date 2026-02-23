<?php

declare(strict_types=1);

use App\Models\User;

test('displays preferences page with two-factor data for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.preferences'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('profile/preferences')
                ->has('twoFactorEnabled')
                ->has('twoFactorPendingConfirmation')
                ->has('requiresConfirmation')
        );
});

test('shows two-factor as disabled when not configured', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.preferences'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('profile/preferences')
                ->where('twoFactorEnabled', false)
                ->where('twoFactorPendingConfirmation', false)
        );
});

test('shows two-factor as pending when enabled but not confirmed', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('profile.preferences'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('profile/preferences')
                ->where('twoFactorEnabled', false)
                ->where('twoFactorPendingConfirmation', true)
        );
});

test('shows two-factor as enabled when confirmed', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('profile.preferences'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('profile/preferences')
                ->where('twoFactorEnabled', true)
                ->where('twoFactorPendingConfirmation', false)
        );
});

test('guests are redirected to login', function () {
    $this->get(route('profile.preferences'))
        ->assertRedirect(route('login'));
});
