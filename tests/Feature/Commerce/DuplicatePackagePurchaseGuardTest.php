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
use App\Services\UserPackagePurchaseGuard;
use App\Services\Zarinpal\ZarinpalRequestResult;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ));
    });
});

function fullPackage(): CoursePackage
{
    return CoursePackage::query()->where('slug', 'full')->firstOrFail();
}

function chapterPackage(string $slug = 'chapter-2'): CoursePackage
{
    return CoursePackage::query()->where('slug', $slug)->firstOrFail();
}

function confirmFullCashUrl(): string
{
    return route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']);
}

function confirmFullInstallmentUrl(): string
{
    return route('checkout.confirm', ['package' => 'full', 'payment' => 'installment']);
}

function confirmChapterUrl(string $slug = 'chapter-2'): string
{
    return route('checkout.confirm', ['package' => 'chapter', 'chapter' => $slug]);
}

function storeFullCashOrder(User $user): TestResponse
{
    return test()->actingAs($user)
        ->from(confirmFullCashUrl())
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
            ...validCheckoutCustomer(),
        ]);
}

function assertDuplicateBlocked(
    TestResponse $response,
    User $user,
    string $confirmUrl,
    int $expectedUserOrderCount,
): void {
    $response
        ->assertRedirect($confirmUrl)
        ->assertSessionHasErrors(['package' => UserPackagePurchaseGuard::BLOCKING_MESSAGE]);

    expect($user->orders()->count())->toBe($expectedUserOrderCount);
}

test('purchase guard detects pending order as blocking access', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Pending]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
    ]);

    $guard = app(UserPackagePurchaseGuard::class);

    expect($guard->hasBlockingAccess($user, $package))->toBeTrue();
});

test('purchase guard detects manual review order as blocking access', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    SpotPlayerLicense::factory()
        ->revoked()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
        ]);

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

    expect(app(UserPackagePurchaseGuard::class)->hasBlockingAccess($user, $package))->toBeTrue();
});

test('pending zarinpal order blocks duplicate full cash checkout', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Pending]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
    ]);

    $response = storeFullCashOrder($user);

    assertDuplicateBlocked($response, $user, confirmFullCashUrl(), 1);
});

test('card to card reviewing order blocks duplicate checkout', function () {
    Storage::fake('local');
    config([
        'card_to_card.card_number' => '6037-9912-3456-7890',
        'card_to_card.card_owner_name' => 'انیماتورشو',
    ]);

    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

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

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('request');
    });

    $response = $this->actingAs($user)
        ->from(confirmFullCashUrl())
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
            'payment_channel' => 'card_to_card',
            ...validCheckoutCustomer(),
            'receipt_image' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

    assertDuplicateBlocked($response, $user, confirmFullCashUrl(), 1);
});

test('installment review order blocks duplicate installment checkout', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->installment()
        ->create();

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Installment,
        'status' => PaymentStatus::Reviewing,
    ]);

    $response = $this->actingAs($user)
        ->from(confirmFullInstallmentUrl())
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'installment',
            ...validCheckoutCustomer(),
            'installment_term' => 'one_month',
        ]);

    assertDuplicateBlocked($response, $user, confirmFullInstallmentUrl(), 1);
});

test('installment down payment pending order does not block re-initiating checkout', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->installmentDownPaymentPending()
        ->create();

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Installment,
        'status' => PaymentStatus::Pending,
    ]);

    // An abandoned down payment must not lock the user out; they can retry from checkout.
    $response = $this->actingAs($user)
        ->from(confirmFullInstallmentUrl())
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'installment',
            ...validCheckoutCustomer(),
            'installment_term' => 'one_month',
        ]);

    $response->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect(Order::query()->where('user_id', $user->id)->count())->toBe(2);
});

test('paid order with pending license blocks duplicate checkout', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

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
            'course_package_id' => $package->id,
            'status' => SpotPlayerLicenseStatus::Pending,
        ]);

    assertDuplicateBlocked(storeFullCashOrder($user), $user, confirmFullCashUrl(), 1);
});

test('active license blocks duplicate checkout', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

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
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-ACTIVE-1234',
        ]);

    assertDuplicateBlocked(storeFullCashOrder($user), $user, confirmFullCashUrl(), 1);
});

test('pending license only blocks duplicate checkout', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    SpotPlayerLicense::factory()->create([
        'user_id' => $user->id,
        'course_package_id' => $package->id,
        'order_id' => null,
        'status' => SpotPlayerLicenseStatus::Pending,
    ]);

    assertDuplicateBlocked(storeFullCashOrder($user), $user, confirmFullCashUrl(), 0);
});

test('revoked license plus reviewing order blocks duplicate checkout', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    SpotPlayerLicense::factory()
        ->revoked()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-REVOKED-SECRET',
        ]);

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

    assertDuplicateBlocked(storeFullCashOrder($user), $user, confirmFullCashUrl(), 1);
});

test('failed order only allows duplicate checkout retry', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Failed]);

    storeFullCashOrder($user)
        ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect($user->orders()->count())->toBe(2);
});

test('cancelled order only allows duplicate checkout retry', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Cancelled]);

    storeFullCashOrder($user)
        ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect($user->orders()->count())->toBe(2);
});

test('gateway failed zarinpal order does not block duplicate checkout', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Failed]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Failed,
    ]);

    storeFullCashOrder($user)
        ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect($user->orders()->count())->toBe(2);
});

test('cancelled pending zarinpal order unblocks duplicate checkout', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Pending]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($user)
        ->post(route('profile.orders.cancel', $order));

    storeFullCashOrder($user)
        ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect($user->orders()->count())->toBe(2);
});

test('revoked license only allows duplicate checkout retry', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    SpotPlayerLicense::factory()
        ->revoked()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-REVOKED-ONLY',
        ]);

    storeFullCashOrder($user)
        ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect($user->orders()->count())->toBe(1);
    expect($user->spotPlayerLicenses()->count())->toBe(1);
});

test('active license on full package does not block different chapter checkout', function () {
    $user = User::factory()->withMobile()->create();
    $full = fullPackage();
    $chapter = chapterPackage('chapter-2');

    $order = Order::factory()
        ->for($user)
        ->forPackage($full)
        ->paid()
        ->create();

    Payment::factory()->forOrder($order)->paid()->create();

    SpotPlayerLicense::factory()
        ->forOrder($order)
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $full->id,
            'license_key' => 'SPOT-FULL-1234',
        ]);

    $this->actingAs($user)
        ->from(confirmChapterUrl('chapter-2'))
        ->post(route('checkout.orders.store'), [
            'package' => 'chapter',
            'payment' => 'cash',
            'chapter' => 'chapter-2',
            ...validCheckoutCustomer(),
        ])
        ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect(Order::query()->where('course_package_id', $chapter->id)->count())->toBe(1);
});

test('checkout confirm shows duplicate purchase notice for blocked user', function () {
    $user = User::factory()->withMobile()->create();
    $package = fullPackage();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Pending]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($user)
        ->get(confirmFullCashUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/confirm')
            ->where('duplicatePurchaseBlocked', true)
            ->where('duplicatePurchaseMessage', UserPackagePurchaseGuard::BLOCKING_MESSAGE)
        );
});

test('checkout confirm does not block guest or clean user', function () {
    $this->get(confirmFullCashUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('duplicatePurchaseBlocked', false)
        );

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)
        ->get(confirmFullCashUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('duplicatePurchaseBlocked', false)
        );
});
