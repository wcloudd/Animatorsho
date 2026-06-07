<?php

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\Zarinpal\ZarinpalRequestResult;
use App\Services\Zarinpal\ZarinpalVerifyResult;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);
});

function mockSuccessfulZarinpalRequest(string $authority = 'A00000000000000000000000000000000000'): void
{
    $gatewayUrl = 'https://sandbox.zarinpal.com/pg/StartPay/'.$authority;

    test()->mock(ZarinpalService::class, function (MockInterface $mock) use ($authority, $gatewayUrl): void {
        $mock->shouldReceive('request')
            ->once()
            ->andReturn(ZarinpalRequestResult::success($authority, $gatewayUrl));
    });
}

test('cash checkout calls zarinpal request and redirects to gateway url', function () {
    mockSuccessfulZarinpalRequest();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ]);

    $order = Order::query()->first();
    $payment = Payment::query()->where('order_id', $order->id)->first();

    $response->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->meta['authority'])->toBe('A00000000000000000000000000000000000')
        ->and($payment->meta)->toHaveKey('requested_at')
        ->and($payment->meta['sandbox'])->toBeTrue();
});

test('cash checkout through inertia returns external location response for zarinpal redirect', function () {
    mockSuccessfulZarinpalRequest();

    $user = User::factory()->create();
    $gatewayUrl = 'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000';

    $response = $this->actingAs($user)
        ->withHeader('X-Inertia', 'true')
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
            ...validCheckoutCustomer(),
        ]);

    $order = Order::query()->first();
    $payment = Payment::query()->where('order_id', $order->id)->first();

    $response
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', $gatewayUrl);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->meta['authority'])->toBe('A00000000000000000000000000000000000');
});

test('cash checkout sends payment amount from database package price to zarinpal', function () {
    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->once()
            ->withArgs(function (Payment $payment): bool {
                return $payment->amount_toman === 5_500_000;
            })
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000001',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000001',
            ));
    });

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ])->assertRedirect();
});

test('gateway request failure keeps order pending marks payment failed and redirects to failed result', function () {
    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->once()
            ->andReturn(ZarinpalRequestResult::failure('Could not connect to Zarinpal.'));
    });

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ]);

    $order = Order::query()->first();
    $payment = Payment::query()->where('order_id', $order->id)->first();

    $response->assertRedirect(route('checkout.result', [
        'status' => 'failed',
        'order' => $order->order_number,
    ]));

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($payment->status)->toBe(PaymentStatus::Failed)
        ->and($payment->meta['gateway_error'])->toBe('Could not connect to Zarinpal.')
        ->and($payment->meta)->toHaveKey('failed_at');
});

test('successful callback marks order and payment paid', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'payment_type' => OrderPaymentType::Cash,
        'final_amount_toman' => 5_500_000,
    ]);

    $authority = 'A00000000000000000000000000000000002';

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
        'amount_toman' => 5_500_000,
        'meta' => [
            'authority' => $authority,
            'requested_at' => now()->toIso8601String(),
        ],
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock) use ($authority): void {
        $mock->shouldReceive('verify')
            ->once()
            ->withArgs(function (Payment $payment, string $receivedAuthority) use ($authority): bool {
                return $receivedAuthority === $authority
                    && $payment->amount_toman === 5_500_000;
            })
            ->andReturn(ZarinpalVerifyResult::success('123456789'));
    });

    $response = $this->get(route('checkout.zarinpal.callback', [
        'Authority' => $authority,
        'Status' => 'OK',
    ]));

    $order->refresh();
    $payment = $order->payments()->first();

    $response->assertRedirect(route('checkout.result', [
        'status' => 'success',
        'order' => $order->order_number,
    ]));

    $license = SpotPlayerLicense::query()->where('order_id', $order->id)->first();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->tracking_code)->toBe('123456789')
        ->and($payment->paid_at)->not->toBeNull()
        ->and($license)->not->toBeNull()
        ->and($license->user_id)->toBe($user->id)
        ->and($license->course_package_id)->toBe($order->course_package_id)
        ->and($license->status)->toBe(SpotPlayerLicenseStatus::Pending)
        ->and($license->license_key)->toBeNull();
});

test('repeated successful callback does not create duplicate license', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'payment_type' => OrderPaymentType::Cash,
        'final_amount_toman' => 5_500_000,
    ]);

    $authority = 'A00000000000000000000000000000000006';

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
        'amount_toman' => 5_500_000,
        'meta' => [
            'authority' => $authority,
        ],
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock) use ($authority): void {
        $mock->shouldReceive('verify')
            ->once()
            ->withArgs(function (Payment $payment, string $receivedAuthority) use ($authority): bool {
                return $receivedAuthority === $authority;
            })
            ->andReturn(ZarinpalVerifyResult::success('123456789'));
    });

    $this->get(route('checkout.zarinpal.callback', [
        'Authority' => $authority,
        'Status' => 'OK',
    ]))->assertRedirect(route('checkout.result', [
        'status' => 'success',
        'order' => $order->order_number,
    ]));

    $firstLicense = SpotPlayerLicense::query()->where('order_id', $order->id)->first();

    $this->get(route('checkout.zarinpal.callback', [
        'Authority' => $authority,
        'Status' => 'OK',
    ]))->assertRedirect(route('checkout.result', [
        'status' => 'success',
        'order' => $order->order_number,
    ]));

    $licenses = SpotPlayerLicense::query()->where('order_id', $order->id)->get();

    expect($licenses)->toHaveCount(1)
        ->and($licenses->first()->id)->toBe($firstLicense->id)
        ->and($licenses->first()->status)->toBe(SpotPlayerLicenseStatus::Pending);
});

test('failed callback does not create spotplayer license', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'payment_type' => OrderPaymentType::Cash,
    ]);

    $authority = 'A00000000000000000000000000000000007';

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
        'meta' => [
            'authority' => $authority,
        ],
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('verify');
    });

    $this->get(route('checkout.zarinpal.callback', [
        'Authority' => $authority,
        'Status' => 'NOK',
    ]))->assertRedirect(route('checkout.result', [
        'status' => 'failed',
        'order' => $order->order_number,
    ]));

    expect(SpotPlayerLicense::query()->count())->toBe(0);
});

test('failed callback marks order and payment failed', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'payment_type' => OrderPaymentType::Cash,
    ]);

    $authority = 'A00000000000000000000000000000000003';

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
        'meta' => [
            'authority' => $authority,
        ],
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('verify');
    });

    $response = $this->get(route('checkout.zarinpal.callback', [
        'Authority' => $authority,
        'Status' => 'NOK',
    ]));

    $order->refresh();
    $payment = $order->payments()->first();

    $response->assertRedirect(route('checkout.result', [
        'status' => 'failed',
        'order' => $order->order_number,
    ]));

    expect($order->status)->toBe(OrderStatus::Failed)
        ->and($payment->status)->toBe(PaymentStatus::Failed);
});

test('verify failure marks order and payment failed', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'payment_type' => OrderPaymentType::Cash,
    ]);

    $authority = 'A00000000000000000000000000000000004';

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
        'meta' => [
            'authority' => $authority,
        ],
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('verify')
            ->once()
            ->andReturn(ZarinpalVerifyResult::failure('Zarinpal rejected the payment verification.'));
    });

    $response = $this->get(route('checkout.zarinpal.callback', [
        'Authority' => $authority,
        'Status' => 'OK',
    ]));

    $order->refresh();
    $payment = $order->payments()->first();

    $response->assertRedirect(route('checkout.result', [
        'status' => 'failed',
        'order' => $order->order_number,
    ]));

    expect($order->status)->toBe(OrderStatus::Failed)
        ->and($payment->status)->toBe(PaymentStatus::Failed);
});

test('already paid callback redirects to success without calling verify again', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->paid()->create([
        'payment_type' => OrderPaymentType::Cash,
    ]);

    $authority = 'A00000000000000000000000000000000005';

    Payment::factory()->forOrder($order)->paid()->create([
        'method' => PaymentMethod::Zarinpal,
        'tracking_code' => '987654321',
        'meta' => [
            'authority' => $authority,
        ],
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('verify');
    });

    $this->get(route('checkout.zarinpal.callback', [
        'Authority' => $authority,
        'Status' => 'OK',
    ]))->assertRedirect(route('checkout.result', [
        'status' => 'success',
        'order' => $order->order_number,
    ]));
});

test('missing or invalid authority redirects safely to checkout result fallback', function () {
    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('verify');
    });

    $this->get(route('checkout.zarinpal.callback'))
        ->assertRedirect(route('checkout.result'));

    $this->get(route('checkout.zarinpal.callback', [
        'Authority' => 'UNKNOWN-AUTHORITY',
        'Status' => 'OK',
    ]))->assertRedirect(route('checkout.result'));
});

test('installment checkout does not call zarinpal', function () {
    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('request');
        $mock->shouldNotReceive('verify');
    });

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'installment',
        ...validCheckoutCustomer(),
        'installment_term' => 'one_month',
    ])->assertRedirect(route('checkout.result', [
        'status' => 'installment-review',
        'order' => Order::query()->value('order_number'),
    ]));
});

test('zarinpal service converts toman to rial for gateway amounts', function () {
    $service = app(ZarinpalService::class);

    expect($service->amountInRials(5_500_000))->toBe(55_000_000);
});

test('zarinpal service uses official sandbox urls', function () {
    config(['zarinpal.sandbox' => true]);

    $service = app(ZarinpalService::class);

    expect($service->paymentUrl('TEST-AUTHORITY'))
        ->toBe('https://sandbox.zarinpal.com/pg/StartPay/TEST-AUTHORITY');
});

test('zarinpal service uses official production urls', function () {
    config(['zarinpal.sandbox' => false]);

    $service = app(ZarinpalService::class);

    expect($service->paymentUrl('TEST-AUTHORITY'))
        ->toBe('https://payment.zarinpal.com/pg/StartPay/TEST-AUTHORITY');
});
