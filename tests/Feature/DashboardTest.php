<?php

use App\Models\User;

test('dashboard route redirects to home', function () {
    $this->get(route('dashboard'))->assertRedirect(route('home'));
});

test('authenticated users visiting dashboard are redirected to home', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('home'));
});
