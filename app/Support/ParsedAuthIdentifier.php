<?php

namespace App\Support;

class ParsedAuthIdentifier
{
    public const Mobile = 'mobile';

    public const Email = 'email';

    public function __construct(
        public readonly string $type,
        public readonly string $value,
    ) {}
}
