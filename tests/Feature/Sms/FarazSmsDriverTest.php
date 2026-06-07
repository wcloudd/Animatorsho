<?php

use App\Enums\SmsMessageStatus;
use App\Enums\SmsMessageType;
use App\Models\SmsMessage;
use App\Services\Sms\Drivers\FarazSmsDriver;
use App\Services\Sms\SmsService;
use App\Services\Sms\SmsSettingsService;
use Database\Seeders\SmsTemplateSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->seed(SmsTemplateSeeder::class);

    config([
        'sms.driver' => 'farazsms',
        'sms.providers.farazsms' => [
            'api_key' => 'test-api-key',
            'sender' => '90008361',
            'base_url' => 'https://api.iranpayamak.com/ws/v1',
        ],
    ]);
});

function enableSmsForFarazTests(): void
{
    app(SmsSettingsService::class)->update([
        'enabled' => true,
        'admin_notifications_enabled' => true,
        'admin_mobile' => '09121111111',
    ]);
}

function farazSimpleSuccessResponse(int $messageId = 1123544244): array
{
    return [
        'status' => 'success',
        'message' => 'انجام شد',
        'data' => [
            'message_id' => $messageId,
        ],
    ];
}

function farazSimpleFailureResponse(): array
{
    return [
        'status' => 'error',
        'message' => 'اطلاعات وارد شده صحیح نمی باشد',
        'code' => '400-1',
    ];
}

test('farazsms driver sends via http fake and marks message sent', function () {
    Http::fake([
        'api.iranpayamak.com/ws/v1/sms/simple' => Http::response(farazSimpleSuccessResponse(), 201),
    ]);

    enableSmsForFarazTests();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message)->not->toBeNull()
        ->and($message->status)->toBe(SmsMessageStatus::Sent)
        ->and($message->provider)->toBe('farazsms')
        ->and($message->meta['provider_message_id'])->toBe(1123544244)
        ->and($message->meta['provider_message'])->toBe('انجام شد')
        ->and($message->meta['provider_http_status'])->toBe(201);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $request->url() === 'https://api.iranpayamak.com/ws/v1/sms/simple'
            && $request->hasHeader('Api-Key', 'test-api-key')
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('Content-Type', 'application/json')
            && ($body['text'] ?? null) === 'پیام تست'
            && ($body['line_number'] ?? null) === '90008361'
            && ($body['recipients'][0] ?? null) === '09121234567'
            && ($body['number_format'] ?? null) === 'english'
            && array_key_exists('schedule', $body)
            && $body['schedule'] === null;
    });
});

test('farazsms api failure marks sms message failed with safe meta', function () {
    Http::fake([
        'api.iranpayamak.com/ws/v1/sms/simple' => Http::response(farazSimpleFailureResponse(), 401),
    ]);

    enableSmsForFarazTests();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Failed)
        ->and($message->meta['provider_error'])->toBe('send_rejected')
        ->and($message->meta['http_status'])->toBe(401)
        ->and($message->meta['provider_message_code'])->toBe('400-1')
        ->and($message->meta['provider_message'])->toBe('اطلاعات وارد شده صحیح نمی باشد')
        ->and($message->meta['response_keys'])->toBe(['status', 'message', 'code'])
        ->and($message->meta['response_preview'])->toBeString()
        ->and(mb_strlen($message->meta['response_preview']))->toBeLessThanOrEqual(200)
        ->and($message->meta)->not->toHaveKey('api_key')
        ->and(json_encode($message->meta))->not->toContain('test-api-key');
});

test('farazsms errors array rejection extracts safe diagnostic meta', function () {
    Http::fake([
        'api.iranpayamak.com/ws/v1/sms/simple' => Http::response([
            'status' => 'error',
            'errors' => ['خط ارسال غیرفعال است', 'اعتبار کافی نیست'],
        ], 422),
    ]);

    enableSmsForFarazTests();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Failed)
        ->and($message->meta['provider_error'])->toBe('send_rejected')
        ->and($message->meta['http_status'])->toBe(422)
        ->and($message->meta['provider_message'])->toBe('خط ارسال غیرفعال است; اعتبار کافی نیست')
        ->and($message->meta['response_keys'])->toBe(['status', 'errors'])
        ->and(json_encode($message->meta))->not->toContain('test-api-key');
});

test('farazsms errors object rejection extracts safe diagnostic meta', function () {
    Http::fake([
        'api.iranpayamak.com/ws/v1/sms/simple' => Http::response([
            'status' => 'error',
            'errors' => [
                'line_number' => ['شماره فرستنده مجاز نیست'],
                'recipients' => ['فرمت گیرنده نامعتبر است'],
            ],
        ], 400),
    ]);

    enableSmsForFarazTests();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Failed)
        ->and($message->meta['provider_error'])->toBe('send_rejected')
        ->and($message->meta['http_status'])->toBe(400)
        ->and($message->meta['provider_message'])->toBe('line_number: شماره فرستنده مجاز نیست; recipients: فرمت گیرنده نامعتبر است')
        ->and($message->meta['response_keys'])->toBe(['status', 'errors'])
        ->and(json_encode($message->meta))->not->toContain('test-api-key');
});

test('missing farazsms credentials marks failed and sends no http', function () {
    Http::fake();

    config([
        'sms.providers.farazsms' => [
            'api_key' => '',
            'sender' => '',
            'base_url' => 'https://api.iranpayamak.com/ws/v1',
        ],
    ]);

    enableSmsForFarazTests();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Failed)
        ->and($message->meta['provider_error'])->toBe('configuration_missing');

    Http::assertNothingSent();
});

test('farazsms invalid non-json response marks message failed with safe meta', function () {
    $invalidBody = 'Api-Key: test-api-key — Gateway error. '.str_repeat('x', 300);

    Http::fake([
        'api.iranpayamak.com/ws/v1/sms/simple' => Http::response($invalidBody, 502),
    ]);

    enableSmsForFarazTests();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Failed)
        ->and($message->meta['provider_error'])->toBe('invalid_response')
        ->and($message->meta['http_status'])->toBe(502)
        ->and($message->meta['response_preview'])->toBeString()
        ->and(mb_strlen($message->meta['response_preview']))->toBe(200)
        ->and($message->meta['response_preview'])->not->toContain('test-api-key')
        ->and($message->meta['response_preview'])->toContain('[REDACTED]')
        ->and($message->meta)->not->toHaveKey('api_key')
        ->and(json_encode($message->meta))->not->toContain('test-api-key');
});

test('farazsms connection failure marks message failed safely', function () {
    Http::fake(function () {
        throw new RuntimeException('Connection refused');
    });

    enableSmsForFarazTests();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Failed)
        ->and($message->meta['provider_error'])->toBe('connection_failed');
});

test('sms service does not throw when farazsms driver throws', function () {
    Http::fake([
        'api.iranpayamak.com/ws/v1/sms/simple' => Http::response(farazSimpleSuccessResponse(), 201),
    ]);

    enableSmsForFarazTests();

    $this->mock(FarazSmsDriver::class, function ($mock): void {
        $mock->shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('Unexpected driver failure'));
    });

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Failed)
        ->and($message->meta['provider_error'])->toBe('driver_exception');
});

test('farazsms logs do not contain api key', function () {
    Log::spy();

    Http::fake([
        'api.iranpayamak.com/ws/v1/sms/simple' => Http::response(farazSimpleFailureResponse(), 401),
    ]);

    enableSmsForFarazTests();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    Log::shouldHaveReceived('warning')
        ->withArgs(function (string $message, array $context): bool {
            $encoded = json_encode($context);

            return $message === 'FarazSMS rejected message.'
                && is_string($encoded)
                && ! str_contains($encoded, 'test-api-key');
        });
});

test('farazsms driver sends normalized iranian mobile as recipient', function () {
    Http::fake([
        'api.iranpayamak.com/ws/v1/sms/simple' => Http::response(farazSimpleSuccessResponse(), 201),
    ]);

    $message = SmsMessage::factory()->create([
        'mobile' => '09121234567',
        'message' => 'پیام',
    ]);

    app(FarazSmsDriver::class)->send($message);

    Http::assertSent(fn ($request) => ($request->data()['recipients'][0] ?? null) === '09121234567');
});

test('farazsms success on http 200 with status success marks message sent', function () {
    Http::fake([
        'api.iranpayamak.com/ws/v1/sms/simple' => Http::response(farazSimpleSuccessResponse(555), 200),
    ]);

    enableSmsForFarazTests();

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    $message = SmsMessage::query()->first();

    expect($message->status)->toBe(SmsMessageStatus::Sent)
        ->and($message->meta['provider_http_status'])->toBe(200)
        ->and($message->meta['provider_message_id'])->toBe(555);
});
