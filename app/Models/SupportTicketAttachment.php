<?php

namespace App\Models;

use App\Services\SupportTicketAttachmentStorageService;
use Database\Factories\SupportTicketAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'support_ticket_message_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size_bytes',
])]
class SupportTicketAttachment extends Model
{
    /** @use HasFactory<SupportTicketAttachmentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (SupportTicketAttachment $attachment): void {
            app(SupportTicketAttachmentStorageService::class)->delete($attachment->path);
        });
    }

    /**
     * @return BelongsTo<SupportTicketMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportTicketMessage::class, 'support_ticket_message_id');
    }
}
