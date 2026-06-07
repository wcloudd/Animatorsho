<?php

use App\Models\User;
use App\Support\AuthRedirect;

test('valid relative redirect paths are accepted', function () {
    expect(AuthRedirect::isValidRelativePath('/checkout/confirm?package=full'))
        ->toBeTrue();
});

test('external and invalid redirect paths are rejected', function () {
    expect(AuthRedirect::isValidRelativePath('//evil.test'))
        ->toBeFalse()
        ->and(AuthRedirect::isValidRelativePath('https://evil.test'))
        ->toBeFalse()
        ->and(AuthRedirect::isValidRelativePath(null))
        ->toBeFalse();
});

test('login redirects back to checkout confirm when redirect query is provided', function () {
    $user = User::factory()->create();
    $target = route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'cash',
    ], absolute: false);

    $this->get(route('login', ['redirect' => $target]));

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect($target);
});

test('registration redirects back to checkout confirm when redirect query is provided', function () {
    $target = route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'installment',
    ], absolute: false);

    $this->get(route('register', ['redirect' => $target]));

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'checkout-redirect@example.com',
        'mobile' => '09121234567',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect($target);
});
