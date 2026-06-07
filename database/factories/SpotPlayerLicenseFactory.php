<?php

namespace Database\Factories;

use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpotPlayerLicense>
 */
class SpotPlayerLicenseFactory extends Factory
{
    protected $model = SpotPlayerLicense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $package = CoursePackage::factory()->create();

        return [
            'user_id' => User::factory(),
            'course_package_id' => $package->id,
            'order_id' => null,
            'license_key' => null,
            'status' => SpotPlayerLicenseStatus::Pending,
            'activated_at' => null,
            'meta' => null,
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $order->user_id ?? User::factory(),
            'course_package_id' => $order->course_package_id,
            'order_id' => $order->id,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SpotPlayerLicenseStatus::Active,
            'license_key' => fake()->uuid(),
            'activated_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SpotPlayerLicenseStatus::Revoked,
            'license_key' => fake()->uuid(),
            'activated_at' => now()->subDay(),
        ]);
    }
}
