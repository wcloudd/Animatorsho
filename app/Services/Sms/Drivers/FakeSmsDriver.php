<?php

namespace App\Services\Sms\Drivers;

use App\Models\SmsMessage;
use App\Services\Sms\Contracts\SmsDriver;
use App\Services\Sms\SmsSendResult;

class FakeSmsDriver implements SmsDriver
{
    public function send(SmsMessage $message): SmsSendResult
    {
        return SmsSendResult::success();
    }
}
