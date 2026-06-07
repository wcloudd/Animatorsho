<?php

namespace App\Models;

use App\Enums\SmsMessageStatus;
use App\Enums\SmsMessageType;
use Database\Factories\SmsMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'mobile',
    'message',
    'type',
    'status',
    'provider',
    'meta',
    'sent_at',
])]
class SmsMessage extends Model
{
    /** @use HasFactory<SmsMessageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SmsMessageStatus::class,
            'meta' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function messageType(): ?SmsMessageType
    {
        return is_string($this->type) ? SmsMessageType::tryFrom($this->type) : null;
    }
}
