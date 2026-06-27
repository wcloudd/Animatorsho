<?php

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    Storage::fake('local');

    config([
        'card_to_card.card_number' => '6037-9912-3456-7890',
        'card_to_card.card_owner_name' => 'انیماتورشو',
    ]);

    // The down payment is settled via receipt, so the gateway must never run.
    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('request');
    });
});

function submitInstallmentCardToCardDownPayment(User $user, ?UploadedFile $receipt = null)
{
    return test()->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'installment',
        'payment_channel' => 'card_to_card',
        'installment_term' => 'one_month',
        ...validCheckoutCustomer(),
        'receipt_image' => $receipt ?? UploadedFile::fake()->image('down-payment.jpg'),
    ]);
}

test('installment checkout can still start an online down payment as before', function () {
    // Re-mock so the online gateway path is exercised.
    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')->andReturn(
            App\Services\Zarinpal\ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ),
        );
    });

    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'installment',
        'installment_term' => 'one_month',
        ...validCheckoutCustomer(),
    ])->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    $order = Order::query()->first();

    expect($order->status)->toBe(OrderStatus::InstallmentDownPaymentPending)
        ->and($order->payment_type)->toBe(OrderPaymentType::Installment);
});

test('installment checkout can submit a card-to-card down payment receipt', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $response = submitInstallmentCardToCardDownPayment($user);

    $order = Order::query()->first();

    $response->assertRedirect(route('checkout.result', [
        'status' => 'manual-review',
        'order' => $order->order_number,
    ]));

    expect($order->status)->toBe(OrderStatus::InstallmentDownPaymentReview)
        ->and($order->payment_type)->toBe(OrderPaymentType::Installment)
        ->and($order->amount_toman)->toBe(5_500_000)
        ->and($order->final_amount_toman)->toBe(6_000_000);

    $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

    // The amount shown/charged is the down payment, not the full course price.
    expect($payment->method)->toBe(PaymentMethod::Installment)
        ->and($payment->status)->toBe(PaymentStatus::Reviewing)
        ->and($payment->amount_toman)->toBe(2_400_000)
        ->and($payment->meta['down_payment_channel'])->toBe('card_to_card')
        ->and($payment->meta['down_payment_toman'])->toBe(2_400_000)
        ->and($payment->meta)->toHaveKey('receipt_path');
});

test('card-to-card installment down payment receipt is stored privately', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    submitInstallmentCardToCardDownPayment($user);

    $payment = Payment::query()->firstOrFail();
    $path = $payment->meta['receipt_path'];

    expect($path)->toStartWith('payment-receipts/');
    Storage::disk('local')->assertExists($path);

    // The receipt route is admin-protected: a non-admin buyer cannot reach it.
    $this->get(route('admin.payments.receipt', $payment))->assertForbidden();
});

test('installment card-to-card down payment requires a receipt image', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)
        ->from(route('checkout.confirm', ['package' => 'full', 'payment' => 'installment']))
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'installment',
            'payment_channel' => 'card_to_card',
            'installment_term' => 'one_month',
            ...validCheckoutCustomer(),
        ])
        ->assertSessionHasErrors(['receipt_image']);

    expect(Order::query()->count())->toBe(0);
});

test('installment card-to-card down payment rejects invalid receipt files', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    submitInstallmentCardToCardDownPayment(
        $user,
        UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
    )->assertSessionHasErrors(['receipt_image']);

    expect(Order::query()->count())->toBe(0);
});

test('admin approving the down payment receipt moves order to installment review without a license', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withMobile('09121234567')->create();

    submitInstallmentCardToCardDownPayment($user);

    $order = Order::query()->firstOrFail();
    $payment = $order->payments()->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.payments.approve', $payment))
        ->assertRedirect();

    $order->refresh();
    $payment->refresh();

    // Same state the online down payment reaches; no license issued yet.
    expect($order->status)->toBe(OrderStatus::InstallmentReview)
        ->and($payment->status)->toBe(PaymentStatus::Reviewing)
        ->and($payment->meta)->toHaveKey('down_payment_paid_at')
        ->and($payment->paid_at)->not->toBeNull()
        ->and(SpotPlayerLicense::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('admin rejecting the down payment receipt grants no access or license', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withMobile('09121234567')->create();

    submitInstallmentCardToCardDownPayment($user);

    $order = Order::query()->firstOrFail();
    $payment = $order->payments()->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.payments.reject', $payment), [
            'note' => 'رسید پیش‌پرداخت ناخواناست.',
        ])
        ->assertRedirect();

    $order->refresh();
    $payment->refresh();

    expect($order->status)->toBe(OrderStatus::InstallmentRejected)
        ->and($payment->status)->toBe(PaymentStatus::Failed)
        ->and($payment->meta['rejection_note'])->toBe('رسید پیش‌پرداخت ناخواناست.')
        ->and(SpotPlayerLicense::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('admin payments index exposes the installment down payment receipt like full card-to-card', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withMobile('09121234567')->create();

    submitInstallmentCardToCardDownPayment($user);

    $payment = Payment::query()->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.payments.index', ['status' => PaymentStatus::Reviewing->value]))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->component('admin/payments/index')
            ->where('payments.data', fn ($payments) => collect($payments)->contains(
                fn (array $item): bool => $item['id'] === $payment->id
                    && $item['methodValue'] === PaymentMethod::Installment->value
                    && $item['isInstallmentDownPaymentReceipt'] === true
                    && $item['canApprove'] === true
                    && is_string($item['receiptUrl']),
            )));
});

test('installment down payment appears under the awaiting-review filter with its receipt', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withMobile('09121234567')->create();

    submitInstallmentCardToCardDownPayment($user);

    $order = Order::query()->firstOrFail();

    // It must appear under "در انتظار بررسی", not only under "همه".
    $this->actingAs($admin)
        ->get(route('admin.installments.index', ['status' => 'awaiting_review']))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->component('admin/installments/index')
            ->where('installments.data', fn ($items) => collect($items)->contains(
                fn (array $item): bool => $item['id'] === $order->id
                    && $item['isInstallmentDownPaymentReceipt'] === true
                    && is_string($item['receiptUrl']),
            )));
});

test('admin can view the private down payment receipt', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withMobile('09121234567')->create();

    submitInstallmentCardToCardDownPayment($user);

    $payment = Payment::query()->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.payments.receipt', $payment))
        ->assertOk();
});

test('duplicate purchase guard blocks a second card-to-card installment request', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    submitInstallmentCardToCardDownPayment($user);

    expect(Order::query()->count())->toBe(1);

    submitInstallmentCardToCardDownPayment($user)
        ->assertSessionHasErrors(['package']);

    expect(Order::query()->count())->toBe(1);
});
