<?php

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('profile requires auth', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));
});

test('authenticated profile loads consolidated access and order history', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create(['name' => 'کاربر تست', 'email' => 'test@example.com']);
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::Pending,
            'payment_type' => OrderPaymentType::Cash,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('profile/index')
            ->where('user.displayName', 'کاربر تست')
            ->where('user.email', 'test@example.com')
            ->where('hasOrderHistory', true)
            ->has('accessItems', 1)
            ->where('accessItems.0.title', 'دوره جامع انیماتورشو')
            ->where('accessItems.0.accessState', 'payment_pending')
            ->where('accessItems.0.statusLabel', 'در انتظار پرداخت')
            ->where('accessItems.0.primaryAction.label', 'ادامه پرداخت')
            ->where('accessItems.0.secondaryAction.label', 'لغو سفارش')
            ->has('orderHistory', 1)
            ->where('orderHistory.0.orderNumber', $order->order_number)
            ->where('orderHistory.0.status', 'در انتظار پرداخت')
            ->where('orderHistory.0.paymentType', 'پرداخت نقدی')
            ->where('orderHistory.0.amountToman', 5_500_000)
        );
});

test('installment reviewing order appears once in access section', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->installment()
        ->create();

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Installment,
        'status' => PaymentStatus::Reviewing,
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accessItems', 1)
            ->where('accessItems.0.accessState', 'installment_reviewing')
            ->where('accessItems.0.statusLabel', 'در انتظار بررسی خرید اقساطی')
            ->where('accessItems.0.description', 'پیش‌پرداخت ۴۰٪ شما با موفقیت ثبت شد و درخواست اقساطی شما در حال بررسی توسط پشتیبانی است.')
            ->has('orderHistory', 1)
        );
});

test('installment rejected order shows honest preserved down payment copy', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->installmentRejected()
        ->create();

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Installment,
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
        'tracking_code' => '999',
        'meta' => [
            'requested_term' => 'one_month',
            'down_payment_paid_at' => now()->toIso8601String(),
            'down_payment_ref' => '999',
            'rejection_note' => 'فعلاً ظرفیت اقساط تکمیل است.',
            'rejected_at' => now()->toIso8601String(),
        ],
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accessItems', 1)
            ->where('accessItems.0.accessState', 'installment_rejected')
            ->where('accessItems.0.description', 'درخواست اقساط رد شد، اما پیش‌پرداخت شما ثبت شده است و پیگیری مالی به‌صورت دستی انجام می‌شود.')
            ->where('accessItems.0.rejectionReason', 'فعلاً ظرفیت اقساط تکمیل است.')
        );
});

test('card to card manual review appears once in access section', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::ManualReview,
            'payment_type' => OrderPaymentType::CardToCard,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::CardToCard,
        'status' => PaymentStatus::Reviewing,
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accessItems', 1)
            ->where('accessItems.0.accessState', 'payment_reviewing')
            ->where('accessItems.0.statusLabel', 'در انتظار بررسی پرداخت')
            ->where('accessItems.0.description', 'رسید شما ثبت شده و پشتیبانی در حال بررسی آن است.')
            ->has('orderHistory', 1)
            ->where('orderHistory.0.status', 'در انتظار بررسی کارت‌به‌کارت')
        );
});

test('paid order with pending license appears as one consolidated access card', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    Payment::factory()->forOrder($order)->paid()->create();

    SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'user_id' => $user->id,
            'license_key' => null,
            'status' => SpotPlayerLicenseStatus::Pending,
        ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accessItems', 1)
            ->where('accessItems.0.accessState', 'paid_license_pending')
            ->where('accessItems.0.statusLabel', 'پرداخت تأیید شده، لایسنس در انتظار فعال‌سازی')
            ->where('accessItems.0.licenseKey', null)
            ->has('orderHistory', 1)
        );
});

test('active license shows key in consolidated access card', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    Payment::factory()->forOrder($order)->paid()->create();

    SpotPlayerLicense::factory()
        ->forOrder($order)
        ->active()
        ->create([
            'user_id' => $user->id,
            'license_key' => 'SPOT-TEST-1234',
        ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accessItems', 1)
            ->where('accessItems.0.accessState', 'access_active')
            ->where('accessItems.0.statusLabel', 'دسترسی فعال')
            ->where('accessItems.0.licenseKey', 'SPOT-TEST-1234')
            ->has('orderHistory', 1)
        );
});

test('license only user appears in access section without order history', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'order_id' => null,
            'license_key' => 'SPOT-ACTIVE-9999',
        ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasOrderHistory', false)
            ->has('orderHistory', 0)
            ->has('accessItems', 1)
            ->where('accessItems.0.accessState', 'access_active')
            ->where('accessItems.0.licenseKey', 'SPOT-ACTIVE-9999')
        );
});

test('revoked license hides key in consolidated access card', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $license = SpotPlayerLicense::factory()
        ->revoked()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-REVOKED-SECRET',
        ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accessItems', 1)
            ->where('accessItems.0.accessState', 'license_revoked')
            ->where('accessItems.0.statusLabel', 'لایسنس غیرفعال شده')
            ->where('accessItems.0.licenseKey', null)
        );

    expect($license->fresh()->license_key)->toBe('SPOT-REVOKED-SECRET');
});

test('failed order appears in access section when no better package state exists', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Failed]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accessItems', 1)
            ->where('accessItems.0.accessState', 'payment_failed')
            ->where('accessItems.0.statusLabel', 'پرداخت ناموفق')
            ->where('accessItems.0.rejectionReason', null)
            ->missing('accessItems.0.meta')
            ->has('orderHistory', 1)
            ->where('orderHistory.0.status', 'ناموفق')
        );
});

test('rejected installment payment exposes rejection reason in profile access', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->installment()
        ->create(['status' => OrderStatus::Failed]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Installment,
        'status' => PaymentStatus::Failed,
        'meta' => [
            'requested_term' => 'one_month',
            'rejection_note' => 'در حال حاضر امکان اقساط فعال نیست.',
            'rejected_at' => now()->toIso8601String(),
        ],
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('accessItems.0.accessState', 'payment_failed')
            ->where('accessItems.0.rejectionReason', 'در حال حاضر امکان اقساط فعال نیست.')
            ->missing('accessItems.0.meta')
        );
});

test('rejected card to card payment exposes rejection reason in profile access', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::Failed,
            'payment_type' => OrderPaymentType::CardToCard,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::CardToCard,
        'status' => PaymentStatus::Failed,
        'meta' => [
            'receipt_path' => 'payment-receipts/1/receipt.jpg',
            'rejection_note' => 'رسید با مبلغ سفارش مطابقت ندارد.',
            'rejected_at' => now()->toIso8601String(),
        ],
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('accessItems.0.accessState', 'payment_failed')
            ->where('accessItems.0.rejectionReason', 'رسید با مبلغ سفارش مطابقت ندارد.')
            ->missing('accessItems.0.meta')
        );
});

test('failed order is hidden from access section when a stronger package state exists', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Failed]);

    $paidOrder = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    Payment::factory()->forOrder($paidOrder)->paid()->create();

    SpotPlayerLicense::factory()
        ->forOrder($paidOrder)
        ->active()
        ->create([
            'user_id' => $user->id,
            'license_key' => 'SPOT-WINNER-1234',
        ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accessItems', 1)
            ->where('accessItems.0.accessState', 'access_active')
            ->where('accessItems.0.licenseKey', 'SPOT-WINNER-1234')
            ->has('orderHistory', 2)
        );
});

test('user cannot see other users orders on profile', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    Order::factory()
        ->for($owner)
        ->forPackage($package)
        ->create(['order_number' => 'AS-20260101-OWNER1']);

    $this->actingAs($otherUser)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasOrderHistory', false)
            ->has('orderHistory', 0)
            ->has('accessItems', 0)
        );
});

test('profile shows empty access state when no orders or licenses exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasOrderHistory', false)
            ->has('orderHistory', 0)
            ->has('accessItems', 0)
        );
});

test('order history keeps raw order details for all attempts', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $failedOrder = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Failed]);

    $reviewOrder = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::ManualReview]);

    Payment::factory()->forOrder($reviewOrder)->create([
        'method' => PaymentMethod::CardToCard,
        'status' => PaymentStatus::Reviewing,
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('accessItems', 1)
            ->where('accessItems.0.accessState', 'payment_reviewing')
            ->has('orderHistory', 2)
            ->where('orderHistory', fn ($history) => collect($history)->contains(
                fn (array $item): bool => $item['id'] === $failedOrder->id
                    && $item['status'] === 'ناموفق',
            ))
            ->where('orderHistory', fn ($history) => collect($history)->contains(
                fn (array $item): bool => $item['id'] === $reviewOrder->id
                    && $item['status'] === 'در انتظار بررسی کارت‌به‌کارت',
            ))
        );
});
