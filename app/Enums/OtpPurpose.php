<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case Login = 'login';
    case Verification = 'verification';
    case Registration = 'registration';
}
