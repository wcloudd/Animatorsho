<?php

namespace App\Models;

use App\Enums\SupportTicketMessageSenderType;
use Database\Factories\SupportTicketMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'support_ticket_id',
    'sender_type',
    'user_id',
    'body',
])]
class SupportTicketMessage extends Model
{
    /** @use HasFactory<SupportTicketMessageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sender_type' => SupportTicketMessageSenderType::class,
        ];
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<SupportTicketAttachment, $this>
     */
    public function attachment(): HasOne
    {
        return $this->hasOne(SupportTicketAttachment::class, 'support_ticket_message_id');
    }
}
