<?php

namespace Database\Factories;

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    protected $model = OtpCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = (string) fake()->numerify('######');

        return [
            'mobile' => '09'.fake()->numerify('#########'),
            'code_hash' => Hash::make($code),
            'purpose' => OtpPurpose::Login,
            'expires_at' => now()->addMinutes((int) config('otp.expires_minutes', 5)),
            'consumed_at' => null,
            'attempts' => 0,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ];
    }

    public function withPlainCode(string $code): static
    {
        return $this->state(fn (): array => [
            'code_hash' => Hash::make($code),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function consumed(): static
    {
        return $this->state(fn (): array => [
            'consumed_at' => now(),
        ]);
    }

    public function maxAttempts(): static
    {
        return $this->state(fn (): array => [
            'attempts' => (int) config('otp.max_attempts', 5),
        ]);
    }

    public function forMobile(string $mobile): static
    {
        return $this->state(fn (): array => [
            'mobile' => $mobile,
        ]);
    }
}
