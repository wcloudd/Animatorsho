<?php

use App\Enums\SmsMessageStatus;
use App\Enums\SmsMessageType;
use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Services\Sms\SmsService;
use App\Services\Sms\SmsSettingsService;
use Database\Seeders\SmsTemplateSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);
});

function enableSmsInDatabase(bool $adminNotifications = true, ?string $adminMobile = '09121111111'): void
{
    app(SmsSettingsService::class)->update([
        'enabled' => true,
        'admin_notifications_enabled' => $adminNotifications,
        'admin_mobile' => $adminMobile,
    ]);
}

test('fake driver creates sent sms message record', function () {
    enableSmsInDatabase();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message)->not->toBeNull()
        ->and($message->mobile)->toBe('09121234567')
        ->and($message->status)->toBe(SmsMessageStatus::Sent)
        ->and($message->provider)->toBe('fake')
        ->and($message->sent_at)->not->toBeNull();
});

test('log driver marks message sent', function () {
    config(['sms.driver' => 'log']);
    enableSmsInDatabase();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::PaymentPaid,
    );

    expect(SmsMessage::query()->first()?->status)->toBe(SmsMessageStatus::Sent)
        ->and(SmsMessage::query()->first()?->provider)->toBe('log');
});

test('globally disabled sms creates skipped message', function () {
    Setting::query()->create([
        'group' => Setting::GROUP_SMS,
        'key' => Setting::KEY_ENABLED,
        'value' => 'false',
    ]);

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Skipped)
        ->and($message->meta['skip_reason'])->toBe('global_disabled');
});

test('disabled template creates skipped message', function () {
    enableSmsInDatabase();

    SmsTemplate::query()
        ->where('key', SmsMessageType::OrderCreated->value)
        ->update(['is_enabled' => false]);

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Skipped)
        ->and($message->meta['skip_reason'])->toBe('template_disabled');
});

test('admin notifications disabled skips admin sms', function () {
    enableSmsInDatabase(adminNotifications: false);

    app(SmsService::class)->sendToAdmin(
        'پیام ادمین',
        SmsMessageType::AdminNewOrder,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Skipped)
        ->and($message->meta['skip_reason'])->toBe('admin_disabled');
});

test('missing admin mobile skips admin sms safely', function () {
    config(['sms.defaults.admin_mobile' => null]);
    enableSmsInDatabase(adminMobile: null);

    app(SmsService::class)->sendToAdmin(
        'پیام ادمین',
        SmsMessageType::AdminNewOrder,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Skipped)
        ->and($message->meta['skip_reason'])->toBe('missing_admin_mobile');
});

test('invalid mobile creates skipped message', function () {
    enableSmsInDatabase();

    app(SmsService::class)->send(
        'invalid',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    expect(SmsMessage::query()->first()?->status)->toBe(SmsMessageStatus::Skipped);
});

test('sms service does not make external http requests', function () {
    Http::fake();
    enableSmsInDatabase();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    Http::assertNothingSent();
});

test('database settings override config defaults', function () {
    config(['sms.defaults.enabled' => false]);

    enableSmsInDatabase();

    expect(app(SmsSettingsService::class)->isEnabled())->toBeTrue();
});

test('farazsms driver through sms service marks message sent', function () {
    config([
        'sms.driver' => 'farazsms',
        'sms.providers.farazsms' => [
            'api_key' => 'test-api-key',
            'sender' => '90008361',
            'base_url' => 'https://api.iranpayamak.com/ws/v1',
        ],
    ]);

    Http::fake([
        'api.iranpayamak.com/ws/v1/sms/simple' => Http::response([
            'status' => 'success',
            'message' => 'انجام شد',
            'data' => ['message_id' => 999],
        ], 201),
    ]);

    enableSmsInDatabase();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    expect(SmsMessage::query()->first()?->status)->toBe(SmsMessageStatus::Sent)
        ->and(SmsMessage::query()->first()?->provider)->toBe('farazsms');
});
