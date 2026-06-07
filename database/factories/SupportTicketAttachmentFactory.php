<?php

namespace Database\Factories;

use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicketAttachment>
 */
class SupportTicketAttachmentFactory extends Factory
{
    protected $model = SupportTicketAttachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_ticket_message_id' => SupportTicketMessage::factory(),
            'disk' => 'local',
            'path' => 'support-attachments/1/1/'.fake()->uuid().'.png',
            'original_name' => 'screenshot.png',
            'mime_type' => 'image/png',
            'size_bytes' => 1024,
        ];
    }

    public function forMessage(SupportTicketMessage $message): static
    {
        return $this->state(fn (): array => [
            'support_ticket_message_id' => $message->id,
            'path' => sprintf(
                'support-attachments/%d/%d/%s.png',
                $message->support_ticket_id,
                $message->id,
                fake()->uuid(),
            ),
        ]);
    }
}
