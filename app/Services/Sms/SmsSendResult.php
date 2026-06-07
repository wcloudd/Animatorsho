<?php

namespace App\Services\Sms;

class SmsSendResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly bool $success,
        public readonly array $meta = [],
    ) {}

    public static function success(array $meta = []): self
    {
        return new self(success: true, meta: $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function failure(array $meta = []): self
    {
        return new self(success: false, meta: $meta);
    }
}
