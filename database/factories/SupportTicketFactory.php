<?php

namespace Database\Factories;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(4),
            'category' => SupportTicketCategory::Other,
            'status' => SupportTicketStatus::Open,
            'customer_name' => fake()->name(),
            'customer_mobile' => fake()->numerify('09#########'),
            'closed_at' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'customer_name' => $user->name,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportTicketStatus::Open,
            'closed_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportTicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    public function withoutMobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_mobile' => null,
        ]);
    }
}
