<?php

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Zarinpal\ZarinpalRequestResult;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
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

test('guest cannot create checkout order and is redirected to login', function () {
    $this->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ])->assertRedirect(route('login'));
});

test('authenticated user with mobile can create checkout order without posting customer_mobile', function () {
    $user = User::factory()->withMobile('09129876543')->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomerNameOnly(),
    ])->assertRedirect();

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->customer_mobile)->toBe('09129876543');
});

test('authenticated user can create full cash order with snapped package price', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $response = $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ]);

    $order = Order::query()->first();

    $response
        ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect($order)->not->toBeNull()
        ->and($order->user_id)->toBe($user->id)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_type)->toBe(OrderPaymentType::Cash)
        ->and($order->amount_toman)->toBe(5_500_000)
        ->and($order->final_amount_toman)->toBe(5_500_000)
        ->and($order->customer_name)->toBe('علی رضایی')
        ->and($order->customer_mobile)->toBe('09121234567');

    $payment = Payment::query()->where('order_id', $order->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->method)->toBe(PaymentMethod::Zarinpal)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount_toman)->toBe(5_500_000);
});

test('authenticated user can create installment review order for full package only', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $response = $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'installment',
        ...validCheckoutCustomer(),
        'installment_term' => 'one_month',
        'note' => 'ترجیح پرداخت ماه اول تا پنجم',
    ]);

    $order = Order::query()->first();

    $response->assertRedirect(route('checkout.result', [
        'status' => 'installment-review',
        'order' => $order->order_number,
    ]));

    expect($order->status)->toBe(OrderStatus::InstallmentReview)
        ->and($order->payment_type)->toBe(OrderPaymentType::Installment)
        ->and($order->amount_toman)->toBe(5_500_000)
        ->and($order->customer_mobile)->toBe('09121234567')
        ->and($order->customer_name)->toBe('علی رضایی');

    $payment = Payment::query()->where('order_id', $order->id)->first();

    expect($payment->method)->toBe(PaymentMethod::Installment)
        ->and($payment->status)->toBe(PaymentStatus::Reviewing)
        ->and($payment->meta)->toMatchArray([
            'requested_term' => 'one_month',
            'note' => 'ترجیح پرداخت ماه اول تا پنجم',
        ])
        ->and($payment->meta)->toHaveKey('submitted_at')
        ->and($payment->meta)->not->toHaveKey('customer_name');
});

test('installment checkout ignores frontend amount fields', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'installment',
        ...validCheckoutCustomer(),
        'installment_term' => 'two_months',
        'amount_toman' => 1,
        'final_amount_toman' => 1,
    ])->assertRedirect();

    $order = Order::query()->first();
    $payment = Payment::query()->where('order_id', $order->id)->first();

    expect($order->amount_toman)->toBe(5_500_000)
        ->and($order->final_amount_toman)->toBe(5_500_000)
        ->and($payment->amount_toman)->toBe(5_500_000);
});

test('checkout snapshots account mobile and ignores posted customer_mobile', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        'customer_name' => 'علی رضایی',
        'customer_mobile' => '+989123456789',
    ])->assertRedirect();

    $order = Order::query()->first();

    expect($order->customer_mobile)->toBe('09121234567');
});

test('checkout ignores invalid posted customer_mobile when account mobile exists', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        'customer_name' => 'علی رضایی',
        'customer_mobile' => '08123456789',
    ])->assertRedirect();

    expect(Order::query()->first()?->customer_mobile)->toBe('09121234567');
});

test('checkout without verified account mobile is redirected before validation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        'customer_name' => 'علی رضایی',
        'customer_mobile' => '08123456789',
    ])->assertRedirect(route('profile.mobile.create'));

    expect(Order::query()->count())->toBe(0);
});

test('checkout requires customer name and uses account mobile when available', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)
        ->from(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
        ])
        ->assertRedirect(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->assertSessionHasErrors(['customer_name']);

    expect(Order::query()->count())->toBe(0);
});

test('installment checkout requires customer name and installment term when account mobile exists', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)
        ->from(route('checkout.confirm', ['package' => 'full', 'payment' => 'installment']))
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'installment',
            'customer_name' => 'علی رضایی',
        ])
        ->assertRedirect(route('checkout.confirm', ['package' => 'full', 'payment' => 'installment']))
        ->assertSessionHasErrors(['installment_term']);

    expect(Order::query()->count())->toBe(0);
});

test('cash checkout does not require installment fields', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ])->assertRedirect();

    expect(Order::query()->count())->toBe(1);
});

test('authenticated user can create chapter cash order from database package', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $response = $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'chapter',
        'payment' => 'cash',
        'chapter' => 'chapter-2',
        ...validCheckoutCustomer(),
    ]);

    $order = Order::query()->with('coursePackage')->first();

    $response->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect($order->coursePackage->slug)->toBe('chapter-2')
        ->and($order->amount_toman)->toBe(1_750_000)
        ->and($order->final_amount_toman)->toBe(1_750_000)
        ->and($order->customer_mobile)->toBe('09121234567');

    $payment = Payment::query()->where('order_id', $order->id)->first();

    expect($payment->method)->toBe(PaymentMethod::Zarinpal)
        ->and($payment->amount_toman)->toBe(1_750_000);
});

test('installment is rejected for chapter package', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)
        ->from(route('checkout.confirm', ['package' => 'chapter', 'chapter' => 'chapter-1']))
        ->post(route('checkout.orders.store'), [
            'package' => 'chapter',
            'payment' => 'installment',
            'chapter' => 'chapter-1',
            ...validCheckoutCustomer(),
        ])
        ->assertRedirect(route('checkout.confirm', ['package' => 'chapter', 'chapter' => 'chapter-1']))
        ->assertSessionHasErrors('payment');

    expect(Order::query()->count())->toBe(0);
});

test('invalid package chapter and payment combinations are rejected', function (array $payload) {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)
        ->from(route('checkout'))
        ->post(route('checkout.orders.store'), [
            ...$payload,
            ...validCheckoutCustomer(),
        ])
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors();

    expect(Order::query()->count())->toBe(0);
})->with([
    'invalid package' => [['package' => 'invalid', 'payment' => 'cash']],
    'missing chapter' => [['package' => 'chapter', 'payment' => 'cash']],
    'invalid chapter slug' => [['package' => 'chapter', 'payment' => 'cash', 'chapter' => 'chapter-99']],
    'invalid payment' => [['package' => 'full', 'payment' => 'invalid']],
]);

test('checkout result page supports payment pending status', function () {
    $this->get(route('checkout.result', [
        'status' => 'payment-pending',
        'order' => 'AS-20260101-ABC123',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('checkout/result'));
});

test('checkout confirm passes order context and customer defaults for full cash', function () {
    $user = User::factory()->withMobile('09121234567')->create(['name' => 'کاربر تست']);

    $this->actingAs($user)->get(route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'cash',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('orderContext.package', 'full')
            ->where('orderContext.payment', 'cash')
            ->where('orderContext.chapter', null)
            ->where('customerDefaults.name', 'کاربر تست')
            ->where('auth.user.mobile', '09121234567')
            ->where('auth.user.mobile_verified_at', fn ($value) => $value !== null)
        );
});

test('checkout confirm passes order context for chapter purchase', function () {
    $this->get(route('checkout.confirm', [
        'package' => 'chapter',
        'chapter' => 'chapter-3',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('orderContext.package', 'chapter')
            ->where('orderContext.payment', 'cash')
            ->where('orderContext.chapter', 'chapter-3')
        );
});
