<?php

namespace App\Models;

use App\Enums\SmsMessageType;
use Database\Factories\SmsTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'title',
    'body',
    'is_enabled',
    'description',
])]
class SmsTemplate extends Model
{
    /** @use HasFactory<SmsTemplateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public function type(): ?SmsMessageType
    {
        return SmsMessageType::tryFrom($this->key);
    }
}
