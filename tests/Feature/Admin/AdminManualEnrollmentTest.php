<?php

use App\Enums\ExternalEnrollmentSource;
use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SmsMessageType;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SmsMessage;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\Admin\AdminUserLookupService;
use App\Services\UserPackagePurchaseGuard;
use Database\Seeders\AnimatorshoCourseSeeder;
use Database\Seeders\SmsTemplateSeeder;
use Inertia\Testing\AssertableInertia as Assert;

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
function manualEnrollmentPayload(CoursePackage $package, array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'خریدار خارجی',
        'user_lookup' => '',
        'customer_mobile' => '09129876543',
        'course_package_id' => $package->id,
        'source' => ExternalEnrollmentSource::Eitaa->value,
        'admin_note' => 'خرید از ایتا',
        'license_key' => '',
    ], $overrides);
}

function manualEnrollmentFullPackage(): CoursePackage
{
    return CoursePackage::query()->where('slug', 'full')->firstOrFail();
}

test('guest cannot access manual enrollment page', function () {
    $this->get(route('admin.manual-enrollments.index'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access manual enrollment page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.manual-enrollments.index'))
        ->assertForbidden();
});

test('admin can load manual enrollment form page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.manual-enrollments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/manual-enrollments/index')
            ->has('packages')
            ->has('sourceOptions')
            ->has('recentGrants'));
});

test('manual enrollment creates new user without password or verified mobile', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'license_key' => 'SP-EXTERNAL-KEY-001',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    $user = User::query()->where('mobile', '09129876543')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('خریدار خارجی')
        ->and($user->hasPassword())->toBeFalse()
        ->and($user->mobile_verified_at)->toBeNull();
});

test('manual enrollment links existing user by mobile without overwriting name', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();
    $existing = User::factory()->create([
        'name' => 'نام قبلی کاربر',
        'mobile' => '09129876543',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'customer_name' => 'نام جدید فرم',
            'license_key' => 'SP-EXTERNAL-KEY-002',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    expect(User::query()->where('mobile', '09129876543')->count())->toBe(1)
        ->and($existing->fresh()->name)->toBe('نام قبلی کاربر');
});

test('manual enrollment normalizes mobile on order', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'customer_mobile' => '989129876543',
            'license_key' => 'SP-EXTERNAL-KEY-003',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    $order = Order::query()->where('customer_mobile', '09129876543')->first();

    expect($order)->not->toBeNull()
        ->and($order->customer_mobile)->toBe('09129876543');
});

test('manual enrollment with license key creates paid external order payment and active license', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'license_key' => 'SP-EXTERNAL-KEY-004',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    $order = Order::query()->where('payment_type', OrderPaymentType::External)->first();
    $payment = Payment::query()->where('order_id', $order?->id)->first();
    $license = SpotPlayerLicense::query()->where('order_id', $order?->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->payment_type)->toBe(OrderPaymentType::External)
        ->and($payment)->not->toBeNull()
        ->and($payment->method)->toBe(PaymentMethod::External)
        ->and($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->meta)->toMatchArray([
            'external_source' => ExternalEnrollmentSource::Eitaa->value,
            'admin_note' => 'خرید از ایتا',
            'granted_by_user_id' => $admin->id,
        ])
        ->and($license)->not->toBeNull()
        ->and($license->status)->toBe(SpotPlayerLicenseStatus::Active)
        ->and($license->license_key)->toBe('SP-EXTERNAL-KEY-004');
});

test('manual enrollment with license key grants course home access', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'license_key' => 'SP-EXTERNAL-KEY-005',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    $user = User::query()->where('mobile', '09129876543')->firstOrFail();

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('animatorsho/course-home'));
});

test('manual enrollment without license key creates pending license and blocks course home', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    $user = User::query()->where('mobile', '09129876543')->firstOrFail();
    $license = SpotPlayerLicense::query()->where('user_id', $user->id)->first();

    expect($license)->not->toBeNull()
        ->and($license->status)->toBe(SpotPlayerLicenseStatus::Pending)
        ->and($license->license_key)->toBeNull();

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertRedirect(route('profile'));
});

test('duplicate guard blocks manual enrollment for same package', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();
    $user = User::factory()->create(['mobile' => '09129876543']);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create([
            'payment_type' => OrderPaymentType::External,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::External,
        'status' => PaymentStatus::Paid,
    ]);

    SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'user_id' => $user->id,
            'status' => SpotPlayerLicenseStatus::Pending,
        ]);

    $orderCountBefore = Order::query()->count();

    $this->actingAs($admin)
        ->from(route('admin.manual-enrollments.index'))
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package))
        ->assertRedirect(route('admin.manual-enrollments.index'))
        ->assertSessionHasErrors([
            'course_package_id' => UserPackagePurchaseGuard::BLOCKING_MESSAGE,
        ]);

    expect(Order::query()->count())->toBe($orderCountBefore);
});

test('profile shows external order and pending access state without license key', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package));

    $user = User::query()->where('mobile', '09129876543')->firstOrFail();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('profile/index')
            ->where('orderHistory.0.paymentType', 'خرید خارج از سایت')
            ->where('accessItems.0.accessState', 'paid_license_pending'));
});

test('manual enrollment does not send payment paid sms', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'license_key' => 'SP-EXTERNAL-KEY-006',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    expect(SmsMessage::query()->where('type', SmsMessageType::PaymentPaid->value)->count())->toBe(0)
        ->and(SmsMessage::query()->where('type', SmsMessageType::CardToCardApproved->value)->count())->toBe(0);
});

test('manual enrollment sends license activated sms when license becomes active', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'license_key' => 'SP-EXTERNAL-KEY-007',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    expect(SmsMessage::query()->where('type', SmsMessageType::LicenseActivated->value)->count())->toBe(1);
});

test('admin orders list shows external payment type and source label', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'license_key' => 'SP-EXTERNAL-KEY-008',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/index')
            ->where('orders.data.0.paymentType', 'خرید خارج از سایت')
            ->where('orders.data.0.externalSourceLabel', 'ایتا'));
});

test('manual enrollment grants access to existing user by user_lookup mobile', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();
    $existing = User::factory()->create([
        'name' => 'کاربر موبایل',
        'mobile' => '09121112233',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'user_lookup' => '09121112233',
            'customer_mobile' => '',
            'customer_name' => 'نام فرم',
            'license_key' => 'SP-LOOKUP-MOBILE-001',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    $order = Order::query()->where('user_id', $existing->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->customer_mobile)->toBe('09121112233')
        ->and($order->customer_name)->toBe('نام فرم')
        ->and($existing->fresh()->name)->toBe('کاربر موبایل')
        ->and(User::query()->count())->toBe(2);
});

test('manual enrollment grants access to existing user by username lookup', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();
    $existing = User::factory()->create([
        'name' => 'کاربر یوزرنیم',
        'username' => 'eitaa_student',
        'mobile' => '09123334455',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'user_lookup' => 'eitaa_student',
            'customer_mobile' => '',
            'customer_name' => 'نام ثبت سفارش',
            'license_key' => 'SP-LOOKUP-USER-001',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    $license = SpotPlayerLicense::query()->where('user_id', $existing->id)->first();
    $order = Order::query()->where('user_id', $existing->id)->first();

    expect($license)->not->toBeNull()
        ->and($license->status)->toBe(SpotPlayerLicenseStatus::Active)
        ->and($order?->customer_mobile)->toBe('09123334455')
        ->and($order?->customer_name)->toBe('نام ثبت سفارش')
        ->and($existing->fresh()->name)->toBe('کاربر یوزرنیم');
});

test('username lookup does not overwrite existing user name', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();
    $existing = User::factory()->create([
        'name' => 'نام اصلی',
        'username' => 'keep_name_user',
        'mobile' => '09124445566',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'user_lookup' => 'keep_name_user',
            'customer_mobile' => '',
            'customer_name' => 'نام جدید فرم',
            'license_key' => 'SP-KEEP-NAME-001',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    expect($existing->fresh()->name)->toBe('نام اصلی');
});

test('unknown username lookup returns validation error and creates no records', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();
    $userCountBefore = User::query()->count();
    $orderCountBefore = Order::query()->count();
    $paymentCountBefore = Payment::query()->count();
    $licenseCountBefore = SpotPlayerLicense::query()->count();

    $this->actingAs($admin)
        ->from(route('admin.manual-enrollments.index'))
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'user_lookup' => 'missing_username',
            'customer_mobile' => '',
            'license_key' => 'SP-MISSING-USER-001',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'))
        ->assertSessionHasErrors([
            'user_lookup' => AdminUserLookupService::USERNAME_NOT_FOUND_MESSAGE,
        ]);

    expect(User::query()->count())->toBe($userCountBefore)
        ->and(Order::query()->count())->toBe($orderCountBefore)
        ->and(Payment::query()->count())->toBe($paymentCountBefore)
        ->and(SpotPlayerLicense::query()->count())->toBe($licenseCountBefore);
});

test('username user without mobile requires customer mobile for enrollment', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();
    $existing = User::factory()->create([
        'username' => 'no_mobile_user',
        'mobile' => null,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.manual-enrollments.index'))
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'user_lookup' => 'no_mobile_user',
            'customer_mobile' => '',
            'license_key' => 'SP-NO-MOBILE-001',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'))
        ->assertSessionHasErrors([
            'customer_mobile' => AdminUserLookupService::MOBILE_REQUIRED_MESSAGE,
        ]);

    expect(Order::query()->where('user_id', $existing->id)->count())->toBe(0);
});

test('username user without mobile can be enrolled when admin provides mobile', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();
    $existing = User::factory()->create([
        'username' => 'needs_mobile_user',
        'mobile' => null,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'user_lookup' => 'needs_mobile_user',
            'customer_mobile' => '09125556677',
            'license_key' => 'SP-LINK-MOBILE-001',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'));

    expect($existing->fresh()->mobile)->toBe('09125556677')
        ->and(Order::query()->where('user_id', $existing->id)->value('customer_mobile'))->toBe('09125556677');
});

test('duplicate guard blocks enrollment when user is resolved by username', function () {
    $admin = User::factory()->admin()->create();
    $package = manualEnrollmentFullPackage();
    $user = User::factory()->create([
        'username' => 'blocked_username',
        'mobile' => '09126667788',
    ]);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create([
            'payment_type' => OrderPaymentType::External,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::External,
        'status' => PaymentStatus::Paid,
    ]);

    SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'user_id' => $user->id,
            'status' => SpotPlayerLicenseStatus::Pending,
        ]);

    $orderCountBefore = Order::query()->count();

    $this->actingAs($admin)
        ->from(route('admin.manual-enrollments.index'))
        ->post(route('admin.manual-enrollments.store'), manualEnrollmentPayload($package, [
            'user_lookup' => 'blocked_username',
            'customer_mobile' => '',
            'license_key' => 'SP-DUP-USER-001',
        ]))
        ->assertRedirect(route('admin.manual-enrollments.index'))
        ->assertSessionHasErrors([
            'course_package_id' => UserPackagePurchaseGuard::BLOCKING_MESSAGE,
        ]);

    expect(Order::query()->count())->toBe($orderCountBefore);
});

test('guest cannot use manual enrollment lookup endpoint', function () {
    $this->getJson(route('admin.manual-enrollments.lookup', [
        'user_lookup' => '09121112233',
    ]))->assertRedirect(route('login'));
});

test('non-admin cannot use manual enrollment lookup endpoint', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->getJson(route('admin.manual-enrollments.lookup', [
            'user_lookup' => '09121112233',
        ]))
        ->assertForbidden();
});

test('lookup by existing mobile returns found user summary', function () {
    $admin = User::factory()->admin()->create();
    $existing = User::factory()->create([
        'name' => 'کاربر موبایل',
        'mobile' => '09121112233',
        'username' => 'mobile_lookup_user',
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.manual-enrollments.lookup', [
            'user_lookup' => '09121112233',
        ]))
        ->assertOk()
        ->assertJson([
            'status' => 'found',
            'message' => 'کاربر پیدا شد',
            'user' => [
                'id' => $existing->id,
                'name' => 'کاربر موبایل',
                'username' => 'mobile_lookup_user',
                'mobile' => '09121112233',
                'hasMobile' => true,
            ],
        ]);
});

test('lookup by existing username returns found user summary', function () {
    $admin = User::factory()->admin()->create();
    $existing = User::factory()->create([
        'name' => 'کاربر یوزرنیم',
        'username' => 'lookup_username',
        'mobile' => '09123334455',
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.manual-enrollments.lookup', [
            'user_lookup' => 'lookup_username',
        ]))
        ->assertOk()
        ->assertJson([
            'status' => 'found',
            'user' => [
                'id' => $existing->id,
                'name' => 'کاربر یوزرنیم',
                'username' => 'lookup_username',
                'mobile' => '09123334455',
                'hasMobile' => true,
            ],
        ]);
});

test('lookup unknown username returns not found and creates nothing', function () {
    $admin = User::factory()->admin()->create();
    $userCountBefore = User::query()->count();

    $this->actingAs($admin)
        ->getJson(route('admin.manual-enrollments.lookup', [
            'user_lookup' => 'missing_lookup_user',
        ]))
        ->assertOk()
        ->assertJson([
            'status' => 'not_found',
            'message' => AdminUserLookupService::USERNAME_NOT_FOUND_MESSAGE,
            'user' => null,
        ]);

    expect(User::query()->count())->toBe($userCountBefore);
});

test('lookup unknown mobile returns not found with new user possible message', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson(route('admin.manual-enrollments.lookup', [
            'user_lookup' => '09127778899',
        ]))
        ->assertOk()
        ->assertJson([
            'status' => 'not_found',
            'message' => AdminUserLookupService::MOBILE_NOT_FOUND_MESSAGE,
            'user' => null,
        ]);
});

test('lookup username user without mobile returns needs mobile', function () {
    $admin = User::factory()->admin()->create();
    $existing = User::factory()->create([
        'username' => 'needs_mobile_lookup',
        'mobile' => null,
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.manual-enrollments.lookup', [
            'user_lookup' => 'needs_mobile_lookup',
        ]))
        ->assertOk()
        ->assertJson([
            'status' => 'needs_mobile',
            'message' => AdminUserLookupService::MOBILE_REQUIRED_MESSAGE,
            'user' => [
                'id' => $existing->id,
                'username' => 'needs_mobile_lookup',
                'hasMobile' => false,
            ],
        ]);
});

test('lookup endpoint does not expose sensitive user fields', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create([
        'mobile' => '09124445566',
        'username' => 'safe_lookup_user',
        'password' => 'secret-password-value',
        'remember_token' => 'remember-token-value',
        'email' => 'safe@example.com',
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.manual-enrollments.lookup', [
            'user_lookup' => '09124445566',
        ]))
        ->assertOk();

    $json = $response->json();

    expect($json)->toHaveKeys(['status', 'message', 'user'])
        ->and(collect($json)->keys()->all())->toBe(['status', 'message', 'user'])
        ->and(collect($json['user'])->keys()->sort()->values()->all())->toBe(
            collect(['id', 'name', 'username', 'mobile', 'hasMobile'])->sort()->values()->all(),
        )
        ->and(json_encode($json))->not->toContain('secret-password-value')
        ->and(json_encode($json))->not->toContain('remember-token-value')
        ->and(json_encode($json))->not->toContain('safe@example.com');
});

test('lookup with empty user lookup returns empty status', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson(route('admin.manual-enrollments.lookup'))
        ->assertOk()
        ->assertJson([
            'status' => 'empty',
            'user' => null,
        ]);
});
