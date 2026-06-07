<?php

namespace App\Services\Sms\Drivers;

use App\Models\SmsMessage;
use App\Services\Sms\Contracts\SmsDriver;
use App\Services\Sms\SmsSendResult;
use Illuminate\Support\Facades\Log;

class LogSmsDriver implements SmsDriver
{
    public function send(SmsMessage $message): SmsSendResult
    {
        Log::info('SMS message sent (log driver).', [
            'mobile' => $message->mobile,
            'type' => $message->type,
            'message' => $message->message,
        ]);

        return SmsSendResult::success();
    }
}
