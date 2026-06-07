<?php

namespace Database\Factories;

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $package = CoursePackage::factory()->create();
        $price = $package->price_toman;

        return [
            'user_id' => User::factory(),
            'course_package_id' => $package->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => OrderStatus::Pending,
            'payment_type' => OrderPaymentType::Cash,
            'amount_toman' => $price,
            'final_amount_toman' => $price,
            'customer_name' => fake()->name(),
            'customer_mobile' => fake()->numerify('09#########'),
        ];
    }

    public function forPackage(CoursePackage $package): static
    {
        return $this->state(fn (array $attributes) => [
            'course_package_id' => $package->id,
            'amount_toman' => $package->price_toman,
            'final_amount_toman' => $package->price_toman,
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
        ]);
    }

    public function installment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => OrderPaymentType::Installment,
            'status' => OrderStatus::InstallmentReview,
        ]);
    }

    public function installmentDownPaymentPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => OrderPaymentType::Installment,
            'status' => OrderStatus::InstallmentDownPaymentPending,
        ]);
    }

    public function installmentRejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => OrderPaymentType::Installment,
            'status' => OrderStatus::InstallmentRejected,
        ]);
    }
}
