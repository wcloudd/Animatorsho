<?php

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SmsMessageStatus;
use App\Enums\SmsMessageType;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\Sms\SmsNotifier;
use App\Services\Sms\SmsSettingsService;
use App\Services\Sms\SmsTemplateService;
use App\Services\Zarinpal\ZarinpalRequestResult;
use App\Services\Zarinpal\ZarinpalVerifyResult;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Database\Seeders\SmsTemplateSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);

    app(SmsSettingsService::class)->update([
        'enabled' => true,
        'admin_notifications_enabled' => true,
        'admin_mobile' => '09121111111',
    ]);
});

test('checkout order creation creates customer and admin sms logs', function () {
    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->once()
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ));
    });

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ])->assertRedirect();

    expect(SmsMessage::query()->where('type', SmsMessageType::OrderCreated->value)->exists())->toBeTrue()
        ->and(SmsMessage::query()->where('type', SmsMessageType::AdminNewOrder->value)->exists())->toBeTrue();
});

test('card to card receipt submission creates submitted and admin review sms logs', function () {
    Storage::fake('local');
    config([
        'card_to_card.card_number' => '6037-9912-3456-7890',
        'card_to_card.card_owner_name' => 'انیماتورشو',
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('request');
    });

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        'payment_channel' => 'card_to_card',
        ...validCheckoutCustomer(),
        'receipt_image' => UploadedFile::fake()->image('receipt.jpg'),
    ])->assertRedirect();

    expect(SmsMessage::query()->where('type', SmsMessageType::CardToCardSubmitted->value)->exists())->toBeTrue()
        ->and(SmsMessage::query()->where('type', SmsMessageType::AdminCardToCardReview->value)->exists())->toBeTrue();
});

test('successful zarinpal callback creates payment paid sms log', function () {
    $user = User::factory()->withMobile()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'payment_type' => OrderPaymentType::Cash,
        'final_amount_toman' => 5_500_000,
        'customer_mobile' => '09121234567',
    ]);

    $authority = 'A00000000000000000000000000000000002';

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
        'amount_toman' => 5_500_000,
        'meta' => ['authority' => $authority],
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('verify')
            ->once()
            ->andReturn(ZarinpalVerifyResult::success('123456789'));
    });

    $this->get(route('checkout.zarinpal.callback', [
        'Authority' => $authority,
        'Status' => 'OK',
    ]))->assertRedirect();

    expect(SmsMessage::query()->where('type', SmsMessageType::PaymentPaid->value)->count())->toBe(1);
});

test('idempotent mark order paid does not create duplicate payment sms', function () {
    $user = User::factory()->withMobile()->create();
    $order = Order::factory()->for($user)->paid()->create([
        'customer_mobile' => '09121234567',
    ]);

    Payment::factory()->forOrder($order)->paid()->create([
        'method' => PaymentMethod::Zarinpal,
    ]);

    $authority = 'A00000000000000000000000000000000003';

    Payment::query()->where('order_id', $order->id)->update([
        'meta' => ['authority' => $authority],
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('verify');
    });

    $this->get(route('checkout.zarinpal.callback', [
        'Authority' => $authority,
        'Status' => 'OK',
    ]))->assertRedirect();

    expect(SmsMessage::query()->where('type', SmsMessageType::PaymentPaid->value)->count())->toBe(0);
});

test('admin card to card approve creates card to card approved sms log', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()->create([
        'course_package_id' => $package->id,
        'status' => OrderStatus::ManualReview,
        'payment_type' => OrderPaymentType::CardToCard,
        'customer_mobile' => '09121234567',
    ]);

    $payment = Payment::factory()->forOrder($order)->cardToCard()->create();
    $path = 'payment-receipts/'.$payment->id.'/receipt.jpg';
    Storage::disk('local')->put($path, 'fake');
    $payment->update(['meta' => ['receipt_path' => $path]]);

    $this->actingAs($admin)
        ->post(route('admin.payments.approve', $payment))
        ->assertRedirect();

    expect(SmsMessage::query()->where('type', SmsMessageType::CardToCardApproved->value)->count())->toBe(1);
});

test('admin card to card reject creates rejected sms log', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()->create([
        'course_package_id' => $package->id,
        'status' => OrderStatus::ManualReview,
        'payment_type' => OrderPaymentType::CardToCard,
        'customer_mobile' => '09121234567',
    ]);

    $payment = Payment::factory()->forOrder($order)->cardToCard()->create();
    $path = 'payment-receipts/'.$payment->id.'/receipt.jpg';
    Storage::disk('local')->put($path, 'fake');
    $payment->update(['meta' => ['receipt_path' => $path]]);

    $this->actingAs($admin)
        ->post(route('admin.payments.reject', $payment), [
            'note' => 'رسید نامعتبر است',
        ])
        ->assertRedirect();

    expect(SmsMessage::query()->where('type', SmsMessageType::CardToCardRejected->value)->count())->toBe(1);
});

test('admin license activation creates license activated sms log', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['customer_mobile' => '09121234567']);
    $license = SpotPlayerLicense::factory()->forOrder($order)->create([
        'status' => SpotPlayerLicenseStatus::Pending,
        'license_key' => null,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.licenses.activate', $license), [
            'license_key' => 'SP-TEST-KEY',
        ])
        ->assertRedirect();

    expect(SmsMessage::query()->where('type', SmsMessageType::LicenseActivated->value)->count())->toBe(1);
});

test('globally disabled sms creates skipped logs from commerce hooks', function () {
    app(SmsSettingsService::class)->update([
        'enabled' => false,
        'admin_notifications_enabled' => true,
        'admin_mobile' => '09121111111',
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->once()
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ));
    });

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ])->assertRedirect();

    expect(SmsMessage::query()->where('status', SmsMessageStatus::Skipped)->count())->toBeGreaterThan(0);
});

test('disabled template creates skipped log from commerce hook', function () {
    SmsTemplate::query()
        ->where('key', SmsMessageType::OrderCreated->value)
        ->update(['is_enabled' => false]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->once()
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ));
    });

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ])->assertRedirect();

    expect(
        SmsMessage::query()
            ->where('type', SmsMessageType::OrderCreated->value)
            ->where('status', SmsMessageStatus::Skipped)
            ->exists(),
    )->toBeTrue();
});

test('sms failure does not break checkout order creation', function () {
    $this->mock(SmsTemplateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('render')->andThrow(new RuntimeException('SMS exploded'));
        $mock->shouldReceive('isEnabled')->andReturn(true);
    });

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->once()
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ));
    });

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ])->assertRedirect();

    expect(Order::query()->count())->toBe(1);
});

test('sms notifier catches internal failures without propagating', function () {
    $this->mock(SmsTemplateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('render')->andThrow(new RuntimeException('boom'));
    });

    $notifier = app(SmsNotifier::class);
    $order = Order::factory()->create(['customer_mobile' => '09121234567']);

    expect(fn () => $notifier->notifyOrderCreated($order))->not->toThrow(RuntimeException::class);
});

test('commerce hooks do not make external http requests', function () {
    Http::fake();

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->once()
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ));
    });

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ]);

    Http::assertNothingSent();
});

test('installment checkout creates installment sms logs instead of generic order created', function () {
    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('request');
    });

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'installment',
        ...validCheckoutCustomer(),
        'installment_term' => 'one_month',
    ])->assertRedirect();

    expect(SmsMessage::query()->where('type', SmsMessageType::InstallmentRequestSubmitted->value)->exists())->toBeTrue()
        ->and(SmsMessage::query()->where('type', SmsMessageType::AdminInstallmentReview->value)->exists())->toBeTrue()
        ->and(SmsMessage::query()->where('type', SmsMessageType::OrderCreated->value)->exists())->toBeFalse()
        ->and(SmsMessage::query()->where('type', SmsMessageType::AdminNewOrder->value)->exists())->toBeFalse();
});

test('installment checkout still creates order when sms is disabled', function () {
    app(SmsSettingsService::class)->update([
        'enabled' => false,
        'admin_notifications_enabled' => false,
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('request');
    });

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'installment',
        ...validCheckoutCustomer(),
        'installment_term' => 'one_month',
    ])->assertRedirect();

    expect(Order::query()->count())->toBe(1)
        ->and(SmsMessage::query()->where('type', SmsMessageType::InstallmentRequestSubmitted->value)->where('status', SmsMessageStatus::Skipped)->exists())->toBeTrue();
});

test('installment reject creates installment rejected sms log', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withMobile()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->installment()
        ->create(['customer_mobile' => '09121234567']);

    $payment = Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Installment,
        'status' => PaymentStatus::Reviewing,
        'meta' => ['requested_term' => 'one_month'],
    ]);

    $this->actingAs($admin)
        ->post(route('admin.payments.reject', $payment), [
            'note' => 'فعلاً امکان‌پذیر نیست.',
        ])
        ->assertRedirect();

    expect(SmsMessage::query()->where('type', SmsMessageType::InstallmentRejected->value)->count())->toBe(1);
});

test('installment sms notifier catches internal failures without propagating', function () {
    $this->mock(SmsTemplateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('render')->andThrow(new RuntimeException('boom'));
    });

    $notifier = app(SmsNotifier::class);
    $order = Order::factory()->create(['customer_mobile' => '09121234567']);

    expect(fn () => $notifier->notifyInstallmentRequestSubmitted($order))->not->toThrow(RuntimeException::class);
});
