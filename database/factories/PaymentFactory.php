<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => PaymentMethod::Zarinpal,
            'status' => PaymentStatus::Pending,
            'amount_toman' => 0,
            'tracking_code' => null,
            'paid_at' => null,
            'meta' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Payment $payment): void {
            if ($payment->order_id === null) {
                return;
            }

            $order = Order::query()->find($payment->order_id);

            if ($order !== null) {
                $payment->amount_toman = $order->final_amount_toman;
            }
        });
    }

    public function forOrder(Order $order): static
    {
        return $this->for($order)->state(fn () => [
            'amount_toman' => $order->final_amount_toman,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'tracking_code' => fake()->numerify('################'),
        ]);
    }

    public function cardToCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::CardToCard,
            'status' => PaymentStatus::Reviewing,
        ]);
    }
}
