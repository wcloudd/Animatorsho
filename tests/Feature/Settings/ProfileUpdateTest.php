<?php

use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('profile can be updated with nullable email and avatar preset', function () {
    $user = User::factory()->otpOnly()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.settings'))
        ->patch(route('profile.update'), [
            'name' => 'OTP User',
            'email' => null,
            'avatar_preset' => 'keyframe_teacher',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.settings'));

    $user->refresh();

    expect($user->name)->toBe('OTP User');
    expect($user->email)->toBeNull();
    expect($user->avatar_preset)->toBe('keyframe_teacher');
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

test('otp only user without a password can delete their account', function () {
    $user = User::factory()->otpOnly()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('user with active course access cannot delete their account', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'status' => SpotPlayerLicenseStatus::Active,
            'license_key' => 'SPOT-ACTIVE-DELETE-GUARD',
        ]);

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
