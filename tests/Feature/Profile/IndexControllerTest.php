<?php

declare(strict_types=1);

use App\Models\User;

test('displays profile page for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('profile/index')
                ->has('mustVerifyEmail')
        );
});

test('guests are redirected to login', function () {
    $this->get(route('profile'))
        ->assertRedirect(route('login'));
});
