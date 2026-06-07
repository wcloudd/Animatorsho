<?php

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    Storage::fake('local');

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('request');
    });
});

function configureCardToCardForTests(): void
{
    config([
        'card_to_card.card_number' => '6037-9912-3456-7890',
        'card_to_card.card_owner_name' => 'انیماتورشو',
    ]);
}

test('authenticated user can create full card-to-card order with reviewing payment', function () {
    configureCardToCardForTests();

    $user = User::factory()->withMobile()->create();

    $response = $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        'payment_channel' => 'card_to_card',
        ...validCheckoutCustomer(),
        'receipt_image' => UploadedFile::fake()->image('receipt.jpg'),
    ]);

    $order = Order::query()->first();

    $response->assertRedirect(route('checkout.result', [
        'status' => 'manual-review',
        'order' => $order->order_number,
    ]));

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::ManualReview)
        ->and($order->payment_type)->toBe(OrderPaymentType::CardToCard)
        ->and($order->amount_toman)->toBe(5_500_000)
        ->and($order->final_amount_toman)->toBe(5_500_000);

    $payment = Payment::query()->where('order_id', $order->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->method)->toBe(PaymentMethod::CardToCard)
        ->and($payment->status)->toBe(PaymentStatus::Reviewing)
        ->and($payment->amount_toman)->toBe(5_500_000)
        ->and($payment->meta)->toHaveKey('receipt_path');

    Storage::disk('local')->assertExists($payment->meta['receipt_path']);
});

test('authenticated user can create chapter card-to-card order', function () {
    configureCardToCardForTests();

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'chapter',
        'chapter' => 'chapter-1',
        'payment' => 'cash',
        'payment_channel' => 'card_to_card',
        ...validCheckoutCustomer(),
        'receipt_image' => UploadedFile::fake()->image('receipt.png'),
    ])->assertRedirect();

    $order = Order::query()->first();

    expect($order->status)->toBe(OrderStatus::ManualReview)
        ->and($order->payment_type)->toBe(OrderPaymentType::CardToCard)
        ->and($order->final_amount_toman)->toBe(1_500_000);
});

test('card-to-card checkout requires receipt image', function () {
    configureCardToCardForTests();

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)
        ->from(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
            'payment_channel' => 'card_to_card',
            ...validCheckoutCustomer(),
        ])
        ->assertRedirect(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->assertSessionHasErrors(['receipt_image']);

    expect(Order::query()->count())->toBe(0);
});

test('card-to-card checkout rejects invalid receipt files', function () {
    configureCardToCardForTests();

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)
        ->from(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
            'payment_channel' => 'card_to_card',
            ...validCheckoutCustomer(),
            'receipt_image' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['receipt_image']);

    expect(Order::query()->count())->toBe(0);
});

test('card-to-card checkout ignores tampered amount and uses database snapshot', function () {
    configureCardToCardForTests();

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        'payment_channel' => 'card_to_card',
        'amount_toman' => 1,
        ...validCheckoutCustomer(),
        'receipt_image' => UploadedFile::fake()->image('receipt.webp'),
    ]);

    $order = Order::query()->first();

    expect($order->final_amount_toman)->toBe(5_500_000);

    $payment = Payment::query()->where('order_id', $order->id)->first();

    expect($payment->amount_toman)->toBe(5_500_000);
});

test('card-to-card checkout is rejected when card config is missing', function () {
    config([
        'card_to_card.card_number' => null,
        'card_to_card.card_owner_name' => null,
    ]);

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)
        ->from(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
            'payment_channel' => 'card_to_card',
            ...validCheckoutCustomer(),
            'receipt_image' => UploadedFile::fake()->image('receipt.jpg'),
        ])
        ->assertSessionHasErrors(['payment_channel']);

    expect(Order::query()->count())->toBe(0);
});

test('checkout confirm hides card-to-card transfer details when config is missing', function () {
    config([
        'card_to_card.card_number' => null,
        'card_to_card.card_owner_name' => null,
    ]);

    $this->get(route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'cash',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('cardToCardAvailable', false)
            ->where('cardToCardTransfer', null)
            ->where(
                'cardToCardUnavailableMessage',
                'اطلاعات کارت‌به‌کارت هنوز توسط مدیر سایت تنظیم نشده است.',
            ));
});

test('checkout confirm exposes card-to-card transfer details when configured', function () {
    configureCardToCardForTests();

    $this->get(route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'cash',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('cardToCardAvailable', true)
            ->where('cardToCardTransfer.cardNumber', '6037-9912-3456-7890')
            ->where('cardToCardTransfer.cardOwnerName', 'انیماتورشو')
            ->where('cardToCardUnavailableMessage', null));
});
