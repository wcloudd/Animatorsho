<?php

use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('home is public', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('animatorsho/index'));
});

test('checkout is public', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $this->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/index')
            ->has('fullPackage')
            ->has('chapterPackages'));
});

test('checkout confirm is public', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $this->get(route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'cash',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/confirm')
            ->has('summary'));
});

test('checkout result is public', function () {
    $this->get(route('checkout.result', ['status' => 'success']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('checkout/result'));
});

test('consultation is public', function () {
    $this->get(route('consultation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('consultation/index'));
});

test('support redirects guests to login', function () {
    $this->get(route('support.index'))->assertRedirect(route('login'));
});

test('profile redirects guests to login', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));
});

test('authenticated user can access support', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('support.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('support/index'));
});

test('authenticated user can access profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk();
});
