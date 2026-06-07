<?php

namespace Database\Factories;

use App\Enums\SmsMessageStatus;
use App\Enums\SmsMessageType;
use App\Models\SmsMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsMessage>
 */
class SmsMessageFactory extends Factory
{
    protected $model = SmsMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mobile' => fake()->numerify('09#########'),
            'message' => fake()->sentence(),
            'type' => SmsMessageType::OrderCreated->value,
            'status' => SmsMessageStatus::Sent,
            'provider' => 'log',
            'meta' => null,
            'sent_at' => now(),
        ];
    }

    public function skipped(): static
    {
        return $this->state(fn (): array => [
            'status' => SmsMessageStatus::Skipped,
            'sent_at' => null,
        ]);
    }
}
