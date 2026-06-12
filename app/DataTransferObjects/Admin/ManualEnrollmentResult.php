<?php

namespace App\DataTransferObjects\Admin;

use App\Models\Order;
use App\Models\SpotPlayerLicense;
use App\Models\User;

readonly class ManualEnrollmentResult
{
    public function __construct(
        public User $user,
        public Order $order,
        public SpotPlayerLicense $license,
        public bool $userCreated,
        public bool $licenseActive,
    ) {}
}
