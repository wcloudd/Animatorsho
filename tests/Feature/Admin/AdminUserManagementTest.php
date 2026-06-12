<?php

use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Database\Seeders\SmsTemplateSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AnimatorshoCourseSeeder::class);
    $this->seed(SmsTemplateSeeder::class);
    config([
        'sms.driver' => 'fake',
        'spotplayer.enabled' => false,
    ]);
});

/**
 * @return array<string, mixed>
 */
function adminManagedUserPayload(User $user, array $overrides = []): array
{
    return array_merge([
        'name' => $user->name,
        'username' => $user->username,
        'mobile' => $user->mobile,
        'verify_mobile' => false,
    ], $overrides);
}

test('guest cannot update managed user', function () {
    $user = User::factory()->create();

    $this->patchJson(route('admin.manual-enrollments.users.update', $user), [
        'name' => 'نام جدید',
    ])->assertUnauthorized();
});

test('non-admin cannot update managed user', function () {
    $actor = User::factory()->create(['is_admin' => false]);
    $user = User::factory()->create();

    $this->actingAs($actor)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), [
            'name' => 'نام جدید',
        ])
        ->assertForbidden();
});

test('admin can update managed user name', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'نام قبلی']);

    $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), adminManagedUserPayload($user, [
            'name' => 'نام ادمین',
        ]))
        ->assertOk()
        ->assertJsonPath('user.name', 'نام ادمین');

    expect($user->fresh()->name)->toBe('نام ادمین');
});

test('admin can update managed user username with uniqueness validation', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['username' => 'taken_user']);
    $user = User::factory()->create(['username' => 'old_user']);

    $duplicateUsernameResponse = $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), adminManagedUserPayload($user, [
            'username' => 'taken_user',
        ]));

    $duplicateUsernameResponse
        ->assertUnprocessable();

    expect($duplicateUsernameResponse->json('errors.username'))->not->toBeNull();

    $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), adminManagedUserPayload($user, [
            'username' => 'new_admin_user',
        ]))
        ->assertOk()
        ->assertJsonPath('user.username', 'new_admin_user');

    expect($user->fresh()->username)->toBe('new_admin_user');
});

test('admin can update managed user mobile with uniqueness validation', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->withMobile('09121112233')->create();
    $user = User::factory()->create(['mobile' => '09124445566']);

    $duplicateMobileResponse = $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), adminManagedUserPayload($user, [
            'mobile' => '09121112233',
        ]));

    $duplicateMobileResponse
        ->assertUnprocessable();

    expect($duplicateMobileResponse->json('errors.mobile'))->not->toBeNull();

    $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), adminManagedUserPayload($user, [
            'mobile' => '09129998877',
            'verify_mobile' => true,
        ]))
        ->assertOk()
        ->assertJsonPath('user.mobile', '09129998877')
        ->assertJsonPath('user.mobileVerified', true);

    expect($user->fresh())
        ->mobile->toBe('09129998877')
        ->mobile_verified_at->not->toBeNull();
});

test('admin can verify mobile manually', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withUnverifiedMobile('09123334455')->create();

    $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), adminManagedUserPayload($user, [
            'mobile' => '09123334455',
            'verify_mobile' => true,
        ]))
        ->assertOk()
        ->assertJsonPath('user.mobileVerified', true);

    expect($user->fresh()->mobile_verified_at)->not->toBeNull();
});

test('admin mobile change defaults to verify unless explicitly false', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withMobile('09124445566')->create();

    $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), [
            'name' => $user->name,
            'username' => $user->username,
            'mobile' => '09127776655',
        ])
        ->assertOk()
        ->assertJsonPath('user.mobileVerified', true);

    expect($user->fresh())
        ->mobile->toBe('09127776655')
        ->mobile_verified_at->not->toBeNull();
});

test('admin update ignores email password and is admin fields', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'is_admin' => false,
    ]);

    $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), adminManagedUserPayload($user, [
            'name' => 'نام امن',
            'email' => 'hacked@example.com',
            'password' => 'new-password',
            'is_admin' => true,
        ]))
        ->assertOk();

    expect($user->fresh())
        ->name->toBe('نام امن')
        ->email->toBe('user@example.com')
        ->is_admin->toBeFalse();
});

test('after admin update user can still receive manual enrollment grant', function () {
    $admin = User::factory()->admin()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $user = User::factory()->create([
        'name' => 'نام قبل',
        'username' => 'grant_user',
        'mobile' => '09129876543',
    ]);

    $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), adminManagedUserPayload($user, [
            'name' => 'نام بعد',
            'username' => 'grant_user',
            'mobile' => '09129876543',
            'verify_mobile' => true,
        ]))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), [
            'customer_name' => 'نام بعد',
            'user_lookup' => 'grant_user',
            'customer_mobile' => '',
            'course_package_id' => $package->id,
            'source' => 'eitaa',
            'admin_note' => 'پس از ویرایش',
            'license_key' => 'SP-UPDATED-GRANT-001',
        ])
        ->assertRedirect(route('admin.manual-enrollments.index'));

    expect(Order::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('manual enrollment order snapshot uses updated name and mobile', function () {
    $admin = User::factory()->admin()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $user = User::factory()->create([
        'name' => 'نام قدیم',
        'username' => 'snapshot_user',
        'mobile' => null,
    ]);

    $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), [
            'name' => 'نام جدید',
            'username' => 'snapshot_user',
            'mobile' => '09128887766',
            'verify_mobile' => true,
        ])
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), [
            'customer_name' => 'نام جدید',
            'user_lookup' => 'snapshot_user',
            'customer_mobile' => '09128887766',
            'course_package_id' => $package->id,
            'source' => 'eitaa',
            'admin_note' => '',
            'license_key' => 'SP-SNAPSHOT-001',
        ])
        ->assertRedirect(route('admin.manual-enrollments.index'));

    $order = Order::query()->where('user_id', $user->id)->latest('id')->first();

    expect($order)
        ->not->toBeNull()
        ->customer_name->toBe('نام جدید')
        ->customer_mobile->toBe('09128887766');
});

test('admin can attach mobile to username-only user via update endpoint', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'username' => 'needs_mobile_user',
        'mobile' => null,
    ]);

    $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), [
            'name' => $user->name,
            'username' => 'needs_mobile_user',
            'mobile' => '09126667788',
            'verify_mobile' => true,
        ])
        ->assertOk()
        ->assertJsonPath('user.hasMobile', true)
        ->assertJsonPath('user.mobileVerified', true);

    expect($user->fresh()->mobile)->toBe('09126667788');
});

test('verify mobile requires mobile value', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'username' => 'no_mobile_user',
        'mobile' => null,
    ]);

    $verifyWithoutMobileResponse = $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), [
            'name' => $user->name,
            'username' => 'no_mobile_user',
            'mobile' => '',
            'verify_mobile' => true,
        ]);

    $verifyWithoutMobileResponse
        ->assertUnprocessable();

    expect($verifyWithoutMobileResponse->json('errors.mobile'))->not->toBeNull();
});

test('admin user update response only exposes safe user fields', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'username' => 'safe_admin_user',
        'mobile' => '09124445566',
        'email' => 'safe@example.com',
        'password' => 'secret-password-value',
    ]);

    $response = $this->actingAs($admin)
        ->patchJson(route('admin.manual-enrollments.users.update', $user), adminManagedUserPayload($user, [
            'name' => 'Safe Name',
        ]))
        ->assertOk();

    $json = $response->json();

    expect(collect($json['user'])->keys()->sort()->values()->all())->toBe(
        collect(['id', 'name', 'username', 'mobile', 'hasMobile', 'mobileVerified'])->sort()->values()->all(),
    )
        ->and(json_encode($json))->not->toContain('secret-password-value')
        ->and(json_encode($json))->not->toContain('safe@example.com');
});
