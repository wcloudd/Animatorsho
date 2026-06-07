<?php

use App\Enums\SmsMessageType;
use App\Models\SmsMessage;
use App\Services\Sms\SmsService;
use App\Services\Sms\SmsSettingsService;
use Database\Seeders\SmsTemplateSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(SmsTemplateSeeder::class);
});

test('testing environment defaults sms driver to fake from phpunit configuration', function () {
    expect(config('sms.driver'))->toBe('fake')
        ->and(config('sms.providers.farazsms.api_key'))->toBe('')
        ->and(config('sms.providers.farazsms.sender'))->toBe('');
});

test('sms service does not make real http requests under default test configuration', function () {
    Http::fake();

    app(SmsSettingsService::class)->update([
        'enabled' => true,
        'admin_notifications_enabled' => false,
        'admin_mobile' => null,
    ]);

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    expect(SmsMessage::query()->first()?->provider)->toBe('fake');

    Http::assertNothingSent();
});
