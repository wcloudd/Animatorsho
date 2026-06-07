<?php

namespace App\Services\Sms\Contracts;

use App\Models\SmsMessage;
use App\Services\Sms\SmsSendResult;

interface SmsDriver
{
    public function send(SmsMessage $message): SmsSendResult;
}
