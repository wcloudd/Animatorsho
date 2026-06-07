<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('profile settings page requires auth', function () {
    $this->get(route('profile.settings'))->assertRedirect(route('login'));
});

test('profile settings page is displayed for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.settings'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('profile/settings')
            ->where('account.name', $user->name)
            ->where('account.avatarPreset', null)
            ->has('avatarPresets', 8)
            ->has('passwordRules')
        );
});

test('otp only user can set email', function () {
    $user = User::factory()->otpOnly()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.settings'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'otp-user@example.com',
        ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.settings'));

    expect($user->refresh()->email)->toBe('otp-user@example.com');
});

test('email must be unique when present', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->otpOnly()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.settings'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ]);

    $response->assertSessionHasErrors('email');
});

test('otp only user can set password without current password', function () {
    $user = User::factory()->otpOnly()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.settings'))
        ->put(route('user-password.update'), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.settings'));

    expect(Hash::check('new-password-123', $user->refresh()->password))->toBeTrue();
});

test('user with existing password must provide current password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.settings'))
        ->put(route('user-password.update'), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

    $response->assertSessionHasErrors('current_password');
});

test('wrong current password is rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.settings'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

    $response->assertSessionHasErrors('current_password');
});

test('user can update name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.settings'))
        ->patch(route('profile.update'), [
            'name' => 'نام جدید',
            'email' => $user->email,
        ]);

    $response->assertSessionHasNoErrors();

    expect($user->refresh()->name)->toBe('نام جدید');
});

test('name longer than eighty characters is rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.settings'))
        ->patch(route('profile.update'), [
            'name' => str_repeat('a', 81),
            'email' => $user->email,
        ]);

    $response->assertSessionHasErrors('name');
});

test('user can select valid avatar preset', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.settings'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar_preset' => 'keyframe_happy',
        ]);

    $response->assertSessionHasNoErrors();

    expect($user->refresh()->avatar_preset)->toBe('keyframe_happy');
});

test('invalid avatar preset is rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('profile.settings'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar_preset' => 'invalid_key',
        ]);

    $response->assertSessionHasErrors('avatar_preset');
});

test('selected avatar preset appears in settings and auth props', function () {
    $user = User::factory()->create([
        'avatar_preset' => 'robot_helper',
    ]);

    $this->actingAs($user)
        ->get(route('profile.settings'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.avatarPreset', 'robot_helper')
        );

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('user.avatarPreset', 'robot_helper')
        );
});

test('otp user can login with email and password after setting credentials', function () {
    $user = User::factory()->otpOnly()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'backup@example.com',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'password' => 'backup-password-123',
            'password_confirmation' => 'backup-password-123',
        ])
        ->assertSessionHasNoErrors();

    auth()->logout();

    $response = $this->post(route('login.store'), [
        'email' => 'backup@example.com',
        'password' => 'backup-password-123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});

test('existing email password user still works after profile update', function () {
    $user = User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Existing User Updated',
            'email' => 'existing@example.com',
            'avatar_preset' => 'animator_student',
        ])
        ->assertSessionHasNoErrors();

    auth()->logout();

    $this->post(route('login.store'), [
        'email' => 'existing@example.com',
        'password' => 'password',
    ])->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
});

test('profile page includes settings link', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('user.settingsUrl', route('profile.settings'))
        );
});

test('invalid avatar preset falls back safely on profile page', function () {
    $user = User::factory()->create([
        'avatar_preset' => 'invalid_legacy_key',
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('user.avatarPreset', null)
        );
});
