<?php

namespace App\DataTransferObjects\Admin;

use App\Models\User;

readonly class AdminUserLookupResult
{
    public function __construct(
        public User $user,
        public bool $created,
        public string $orderCustomerMobile,
    ) {}
}
