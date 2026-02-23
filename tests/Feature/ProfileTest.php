<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('profile'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertOk();
});
