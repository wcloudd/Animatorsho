<?php

namespace Database\Factories;

use App\Enums\SupportTicketMessageSenderType;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicketMessage>
 */
class SupportTicketMessageFactory extends Factory
{
    protected $model = SupportTicketMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'sender_type' => SupportTicketMessageSenderType::User,
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }

    public function forTicket(SupportTicket $ticket): static
    {
        return $this->state(fn (array $attributes) => [
            'support_ticket_id' => $ticket->id,
        ]);
    }

    public function fromUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_type' => SupportTicketMessageSenderType::User,
            'user_id' => $user->id,
        ]);
    }

    public function fromAdmin(User $admin): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_type' => SupportTicketMessageSenderType::Admin,
            'user_id' => $admin->id,
        ]);
    }
}
