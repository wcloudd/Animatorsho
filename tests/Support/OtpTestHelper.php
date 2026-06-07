<?php

namespace Tests\Support;

use App\Models\SmsMessage;

class OtpTestHelper
{
    public static function extractCodeFromLastSms(string $mobile): ?string
    {
        $message = SmsMessage::query()
            ->where('mobile', $mobile)
            ->latest('id')
            ->value('message');

        if (! is_string($message)) {
            return null;
        }

        if (preg_match('/(\d{5,6})/', $message, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
