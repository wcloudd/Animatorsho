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
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);
});

test('guest cannot access admin resources', function () {
    $this->get(route('admin.packages.index'))->assertRedirect(route('login'));
    $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
    $this->get(route('admin.licenses.index'))->assertRedirect(route('login'));
});

test('non-admin cannot access admin resources', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.packages.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.orders.index'))
        ->assertForbidden();
});

test('admin can view package order and license resources', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.packages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/packages/index'));

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/orders/index'));

    $this->actingAs($admin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/payments/index'));

    $this->actingAs($admin)
        ->get(route('admin.licenses.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/licenses/index'));
});

test('admin can edit package price', function () {
    $admin = User::factory()->admin()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('admin.packages.update', $package), [
            'title' => $package->title,
            'price_toman' => 7_500_000,
            'is_active' => true,
            'display_order' => $package->display_order,
        ])
        ->assertRedirect(route('admin.packages.index'));

    expect($package->fresh()->price_toman)->toBe(7_500_000);
});

test('editing package price does not change existing order amount_toman', function () {
    $admin = User::factory()->admin()->create();
    $package = CoursePackage::factory()->create(['price_toman' => 1_500_000]);

    $order = Order::factory()->forPackage($package)->create();
    $order->snapshotAmountsFromPackage();
    $order->save();

    $this->actingAs($admin)
        ->patch(route('admin.packages.update', $package), [
            'title' => $package->title,
            'price_toman' => 9_999_999,
            'is_active' => true,
            'display_order' => $package->display_order,
        ])
        ->assertRedirect(route('admin.packages.index'));

    $order->refresh();

    expect($order->amount_toman)->toBe(1_500_000)
        ->and($order->final_amount_toman)->toBe(1_500_000)
        ->and($package->fresh()->price_toman)->toBe(9_999_999);
});

test('admin mark order as paid creates pending license', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Pending]);

    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.mark-paid', $order))
        ->assertRedirect();

    $order->refresh();
    $license = SpotPlayerLicense::query()->where('order_id', $order->id)->first();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($license)->not->toBeNull()
        ->and($license->status)->toBe(SpotPlayerLicenseStatus::Pending)
        ->and($license->license_key)->toBeNull();
});

test('repeated mark paid action does not duplicate license', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Pending]);

    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($admin)->post(route('admin.orders.mark-paid', $order));
    $this->actingAs($admin)->post(route('admin.orders.mark-paid', $order));

    expect(SpotPlayerLicense::query()->where('order_id', $order->id)->count())->toBe(1);
});

test('admin activating license stores license_key and activated_at', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    $license = SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'status' => SpotPlayerLicenseStatus::Pending,
            'license_key' => null,
            'activated_at' => null,
        ]);

    $this->actingAs($admin)
        ->post(route('admin.licenses.activate', $license), [
            'license_key' => 'SP-TEST-KEY-123',
        ])
        ->assertRedirect();

    $license->refresh();

    expect($license->status)->toBe(SpotPlayerLicenseStatus::Active)
        ->and($license->license_key)->toBe('SP-TEST-KEY-123')
        ->and($license->activated_at)->not->toBeNull();
});

test('admin order list includes payment and license context for tracking', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'علی رضایی', 'email' => 'ali@example.com']);
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create([
            'customer_name' => 'علی رضایی',
            'customer_mobile' => '09121234567',
        ]);

    Payment::factory()->forOrder($order)->paid()->create();

    SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create(['status' => SpotPlayerLicenseStatus::Pending]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/index')
            ->has('orders.data')
            ->where('orders.data', fn ($orders) => collect($orders)->contains(
                fn (array $item): bool => $item['orderNumber'] === $order->order_number
                    && $item['userName'] === 'علی رضایی'
                    && $item['userEmail'] === 'ali@example.com'
                    && $item['customerName'] === 'علی رضایی'
                    && $item['customerMobile'] === '09121234567'
                    && $item['latestPaymentStatus'] === 'پرداخت موفق'
                    && $item['latestPaymentMethod'] === 'پرداخت آنلاین (زرین‌پال)'
                    && $item['licenseStatus'] === 'در انتظار فعال‌سازی',
            )));

    $this->actingAs($admin)
        ->get(route('admin.licenses.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/licenses/index')
            ->has('licenses.data')
            ->where('licenses.data', fn ($licenses) => collect($licenses)->contains(
                fn (array $item): bool => $item['orderNumber'] === $order->order_number
                    && $item['userName'] === 'علی رضایی'
                    && $item['userEmail'] === 'ali@example.com'
                    && $item['orderCustomerName'] === 'علی رضایی'
                    && $item['orderCustomerMobile'] === '09121234567'
                    && $item['packageTitle'] === 'دوره جامع انیماتورشو'
                    && $item['latestPaymentStatus'] === 'پرداخت موفق'
                    && $item['orderStatus'] === 'پرداخت موفق'
                    && $item['status'] === 'در انتظار فعال‌سازی',
            )));
});

test('admin can update order customer name and mobile', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'customer_name' => null,
            'customer_mobile' => null,
        ]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.update-customer', $order), [
            'customer_name' => 'سارا محمدی',
            'customer_mobile' => '+989121112233',
        ])
        ->assertRedirect();

    $order->refresh();

    expect($order->customer_name)->toBe('سارا محمدی')
        ->and($order->customer_mobile)->toBe('09121112233');
});

test('admin update order customer rejects invalid mobile', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create([
        'customer_name' => 'علی رضایی',
        'customer_mobile' => null,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.orders.index'))
        ->patch(route('admin.orders.update-customer', $order), [
            'customer_name' => 'علی رضایی',
            'customer_mobile' => 'invalid',
        ])
        ->assertRedirect(route('admin.orders.index'))
        ->assertSessionHasErrors(['customer_mobile']);

    expect($order->fresh()->customer_mobile)->toBeNull();
});

test('non-admin cannot update order customer info', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $order = Order::factory()->create();

    $this->actingAs($user)
        ->patch(route('admin.orders.update-customer', $order), [
            'customer_name' => 'نام جدید',
            'customer_mobile' => '09121234567',
        ])
        ->assertForbidden();
});

test('admin can revoke active license and keeps license key in database', function () {
    $admin = User::factory()->admin()->create();

    $license = SpotPlayerLicense::factory()->active()->create([
        'license_key' => 'SP-KEEP-AFTER-REVOKE',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.licenses.revoke', $license))
        ->assertRedirect();

    $license->refresh();

    expect($license->status)->toBe(SpotPlayerLicenseStatus::Revoked)
        ->and($license->license_key)->toBe('SP-KEEP-AFTER-REVOKE');
});

test('admin can reactivate revoked license with a new key', function () {
    $admin = User::factory()->admin()->create();

    $license = SpotPlayerLicense::factory()->revoked()->create([
        'license_key' => 'SP-OLD-REVOKED-KEY',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.licenses.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('licenses.data', fn ($licenses) => collect($licenses)->contains(
                fn (array $item): bool => $item['id'] === $license->id
                    && $item['canActivate'] === true
                    && $item['licenseKey'] === 'SP-OLD-REVOKED-KEY',
            )));

    $this->actingAs($admin)
        ->post(route('admin.licenses.activate', $license), [
            'license_key' => 'SP-NEW-REACTIVATED-KEY',
        ])
        ->assertRedirect();

    $license->refresh();

    expect($license->status)->toBe(SpotPlayerLicenseStatus::Active)
        ->and($license->license_key)->toBe('SP-NEW-REACTIVATED-KEY')
        ->and($license->activated_at)->not->toBeNull();
});

function createReviewingCardToCardPayment(): Payment
{
    Storage::fake('local');

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::ManualReview,
            'payment_type' => OrderPaymentType::CardToCard,
            'customer_name' => 'علی رضایی',
            'customer_mobile' => '09121234567',
        ]);

    $payment = Payment::factory()
        ->forOrder($order)
        ->cardToCard()
        ->create();

    $path = 'payment-receipts/'.$payment->id.'/receipt.jpg';
    Storage::disk('local')->put($path, 'fake-receipt-content');

    $payment->update([
        'meta' => array_merge($payment->meta ?? [], [
            'receipt_path' => $path,
            'receipt_mime' => 'image/jpeg',
            'receipt_uploaded_at' => now()->toIso8601String(),
        ]),
    ]);

    return $payment->fresh(['order']);
}

test('admin cannot mark card-to-card manual review order paid from orders page', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingCardToCardPayment();
    $order = $payment->order;

    $this->actingAs($admin)
        ->post(route('admin.orders.mark-paid', $order))
        ->assertRedirect();

    $order->refresh();
    $payment->refresh();

    expect($order->status)->toBe(OrderStatus::ManualReview)
        ->and($payment->status)->toBe(PaymentStatus::Reviewing)
        ->and(SpotPlayerLicense::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('admin can approve card-to-card payment and create pending license once', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingCardToCardPayment();
    $order = $payment->order;

    $this->actingAs($admin)
        ->post(route('admin.payments.approve', $payment))
        ->assertRedirect();

    $order->refresh();
    $payment->refresh();
    $license = SpotPlayerLicense::query()->where('order_id', $order->id)->first();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($payment->status)->toBe(PaymentStatus::Paid)
        ->and($license)->not->toBeNull()
        ->and($license->status)->toBe(SpotPlayerLicenseStatus::Pending);

    $this->actingAs($admin)->post(route('admin.payments.approve', $payment));

    expect(SpotPlayerLicense::query()->where('order_id', $order->id)->count())->toBe(1);
});

test('admin can reject card-to-card payment without creating license', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingCardToCardPayment();
    $order = $payment->order;

    $this->actingAs($admin)
        ->post(route('admin.payments.reject', $payment), [
            'note' => 'رسید با مبلغ سفارش مطابقت ندارد.',
        ])
        ->assertRedirect();

    $order->refresh();
    $payment->refresh();

    expect($order->status)->toBe(OrderStatus::Failed)
        ->and($payment->status)->toBe(PaymentStatus::Failed)
        ->and($payment->meta['rejection_note'])->toBe('رسید با مبلغ سفارش مطابقت ندارد.')
        ->and(SpotPlayerLicense::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('non-admin cannot approve or reject card-to-card payment', function () {
    $payment = createReviewingCardToCardPayment();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->post(route('admin.payments.approve', $payment))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('admin.payments.reject', $payment))
        ->assertForbidden();
});

test('guest cannot access card-to-card receipt route', function () {
    $payment = createReviewingCardToCardPayment();

    $this->get(route('admin.payments.receipt', $payment))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access card-to-card receipt route', function () {
    $payment = createReviewingCardToCardPayment();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.payments.receipt', $payment))
        ->assertForbidden();
});

test('admin can access card-to-card receipt route', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingCardToCardPayment();

    $this->actingAs($admin)
        ->get(route('admin.payments.receipt', $payment))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');
});

test('admin payments index exposes card-to-card review fields', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingCardToCardPayment();

    $this->actingAs($admin)
        ->get(route('admin.payments.index', ['status' => PaymentStatus::Reviewing->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/payments/index')
            ->where('payments.data', fn ($payments) => collect($payments)->contains(
                fn (array $item): bool => $item['id'] === $payment->id
                    && $item['customerName'] === 'علی رضایی'
                    && $item['customerMobile'] === '09121234567'
                    && $item['methodValue'] === PaymentMethod::CardToCard->value
                    && $item['canApprove'] === true
                    && $item['canReject'] === true
                    && is_string($item['receiptUrl']),
            )));
});

test('manual review card-to-card orders hide mark paid action in admin order list', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingCardToCardPayment();
    $order = $payment->order;

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['status' => OrderStatus::ManualReview->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.data', fn ($orders) => collect($orders)->contains(
                fn (array $item): bool => $item['id'] === $order->id
                    && $item['canMarkPaid'] === false,
            )));
});

function createReviewingInstallmentPayment(): Payment
{
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->installment()
        ->create([
            'customer_name' => 'سارا محمدی',
            'customer_mobile' => '09129876543',
        ]);

    return Payment::factory()
        ->forOrder($order)
        ->create([
            'method' => PaymentMethod::Installment,
            'status' => PaymentStatus::Reviewing,
            'meta' => [
                'requested_term' => 'two_months',
                'note' => 'ترجیح تماس بعد از ظهر',
                'submitted_at' => now()->toIso8601String(),
            ],
        ])
        ->fresh(['order']);
}

test('admin cannot mark installment review order paid from orders page', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingInstallmentPayment();
    $order = $payment->order;

    $this->actingAs($admin)
        ->post(route('admin.orders.mark-paid', $order))
        ->assertRedirect();

    $order->refresh();
    $payment->refresh();

    expect($order->status)->toBe(OrderStatus::InstallmentReview)
        ->and($payment->status)->toBe(PaymentStatus::Reviewing)
        ->and(SpotPlayerLicense::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('admin can approve installment payment and create pending license once', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingInstallmentPayment();
    $order = $payment->order;

    $this->actingAs($admin)
        ->post(route('admin.payments.approve', $payment))
        ->assertRedirect();

    $order->refresh();
    $payment->refresh();
    $license = SpotPlayerLicense::query()->where('order_id', $order->id)->first();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($payment->status)->toBe(PaymentStatus::Paid)
        ->and($license)->not->toBeNull()
        ->and($license->status)->toBe(SpotPlayerLicenseStatus::Pending);

    $this->actingAs($admin)->post(route('admin.payments.approve', $payment));

    expect(SpotPlayerLicense::query()->where('order_id', $order->id)->count())->toBe(1);
});

test('admin can reject installment payment without creating license', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingInstallmentPayment();
    $order = $payment->order;

    $this->actingAs($admin)
        ->post(route('admin.payments.reject', $payment), [
            'note' => 'در حال حاضر امکان اقساط برای این بسته فعال نیست.',
        ])
        ->assertRedirect();

    $order->refresh();
    $payment->refresh();

    expect($order->status)->toBe(OrderStatus::Failed)
        ->and($payment->status)->toBe(PaymentStatus::Failed)
        ->and($payment->meta['rejection_note'])->toBe('در حال حاضر امکان اقساط برای این بسته فعال نیست.')
        ->and(SpotPlayerLicense::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('admin payments index exposes installment review fields', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingInstallmentPayment();

    $this->actingAs($admin)
        ->get(route('admin.payments.index', ['status' => PaymentStatus::Reviewing->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/payments/index')
            ->where('payments.data', fn ($payments) => collect($payments)->contains(
                fn (array $item): bool => $item['id'] === $payment->id
                    && $item['customerName'] === 'سارا محمدی'
                    && $item['customerMobile'] === '09129876543'
                    && $item['methodValue'] === PaymentMethod::Installment->value
                    && $item['installmentRequestedTerm'] === '۲ ماهه'
                    && $item['installmentNote'] === 'ترجیح تماس بعد از ظهر'
                    && $item['canApprove'] === true
                    && $item['canReject'] === true,
            )));
});

test('installment review orders hide mark paid action in admin order list', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingInstallmentPayment();
    $order = $payment->order;

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['status' => OrderStatus::InstallmentReview->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.data', fn ($orders) => collect($orders)->contains(
                fn (array $item): bool => $item['id'] === $order->id
                    && $item['canMarkPaid'] === false,
            )));
});

test('admin payments index reads legacy installment_term meta key', function () {
    $admin = User::factory()->admin()->create();
    $payment = createReviewingInstallmentPayment();

    $payment->update([
        'meta' => [
            'installment_term' => 'one_month',
            'note' => 'یادداشت قدیمی',
            'submitted_at' => now()->toIso8601String(),
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payments.index', ['status' => PaymentStatus::Reviewing->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.data', fn ($payments) => collect($payments)->contains(
                fn (array $item): bool => $item['id'] === $payment->id
                    && $item['installmentRequestedTerm'] === '۱ ماهه',
            )));
});
