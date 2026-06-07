<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('main login page uses mobile primary auth component', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/login'));
});

test('legacy email login page can be rendered', function () {
    $this->get(route('login.email'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/login-email'));
});

test('users can authenticate using mobile and password', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $response = $this->post(route('login.store'), [
        'mobile' => '09121234567',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('home', absolute: false));
});

test('mobile is normalized before login lookup', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->post(route('login.store'), [
        'mobile' => '+98 912 123 4567',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('users can authenticate using legacy email login route', function () {
    $user = User::factory()->create([
        'email' => 'legacy-user@example.com',
    ]);

    $response = $this->post(route('login.email.store'), [
        'email' => 'legacy-user@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('home', absolute: false));
});

test('admin can authenticate using legacy email login route', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.com',
    ]);

    $this->post(route('login.email.store'), [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin);

    $this->get(route('admin.dashboard'))
        ->assertOk();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.email.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid mobile password', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->post(route('login.store'), [
        'mobile' => '09121234567',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can not authenticate with invalid email password on legacy route', function () {
    $user = User::factory()->create();

    $this->post(route('login.email.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('email password login on main route does not authenticate legacy users', function () {
    $user = User::factory()->create([
        'email' => 'legacy-user@example.com',
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited on mobile login', function () {
    User::factory()->withMobile('09121234567')->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'mobile' => '09121234567',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('mobile');
    }

    $this->post(route('login.store'), [
        'mobile' => '09121234567',
        'password' => 'wrong-password',
    ])->assertStatus(429);
});
