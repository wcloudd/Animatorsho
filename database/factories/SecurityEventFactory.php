<?php

namespace Database\Factories;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityEvent>
 */
class SecurityEventFactory extends Factory
{
    protected $model = SecurityEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => 'honeypot_triggered',
            'occurred_at' => now(),
            'user_id' => User::factory(),
            'route' => 'consultation.store',
            'method' => 'POST',
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'meta' => null,
            'created_at' => now(),
        ];
    }

    public function honeypotTriggered(): static
    {
        return $this->state(fn (): array => [
            'event' => 'honeypot_triggered',
            'route' => 'consultation.store',
            'method' => 'POST',
            'meta' => null,
        ]);
    }

    public function authRateLimitExceeded(): static
    {
        return $this->state(fn (): array => [
            'event' => 'auth_rate_limit_exceeded',
            'route' => 'login.store',
            'method' => 'POST',
            'meta' => [
                'limiter' => 'login',
                'retry_after_seconds' => 60,
            ],
        ]);
    }

    public function paymentRetryCeilingReached(): static
    {
        return $this->state(fn (): array => [
            'event' => 'payment_retry_ceiling_reached',
            'route' => 'profile.orders.retry-online-payment',
            'method' => 'POST',
            'meta' => [
                'order_id' => fake()->numberBetween(1, 9999),
                'payment_id' => fake()->numberBetween(1, 9999),
                'retry_count' => 5,
                'max_retries' => 5,
            ],
        ]);
    }

    public function consultationDuplicateBlocked(): static
    {
        return $this->state(fn (): array => [
            'event' => 'consultation_duplicate_blocked',
            'route' => 'consultation.store',
            'method' => 'POST',
            'meta' => [
                'open_consultation_request_id' => fake()->numberBetween(1, 9999),
            ],
        ]);
    }

    public function supportOpenTicketCapReached(): static
    {
        return $this->state(fn (): array => [
            'event' => 'support_open_ticket_cap_reached',
            'route' => 'support.tickets.store',
            'method' => 'POST',
            'meta' => [
                'open_ticket_count' => 3,
                'max_open_tickets' => 3,
            ],
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
        ]);
    }
}
