<?php

use App\Enums\SmsMessageStatus;
use App\Enums\SmsMessageType;
use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\Sms\SmsService;
use Database\Seeders\SmsTemplateSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(SmsTemplateSeeder::class);
    $this->withoutVite();
});

test('guest cannot view admin sms page', function () {
    $this->get(route('admin.sms.index'))->assertRedirect(route('login'));
});

test('non-admin cannot view admin sms page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.sms.index'))
        ->assertForbidden();
});

test('admin can view admin sms page', function () {
    $admin = User::factory()->admin()->create();

    SmsMessage::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.sms.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/sms/index')
            ->has('settings')
            ->has('settings.driverLabel')
            ->has('settings.driverConfigured')
            ->has('templates', 14)
            ->missing('logs'));
});

test('admin sms page does not expose provider credentials', function () {
    config([
        'sms.driver' => 'farazsms',
        'sms.providers.farazsms' => [
            'api_key' => 'secret-api-key-value',
            'sender' => '90008361',
            'base_url' => 'https://api.iranpayamak.com/ws/v1',
        ],
    ]);

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.sms.index'))
        ->assertOk();

    $encoded = json_encode($response->original->getData()['page']['props'] ?? []);

    expect(is_string($encoded))->toBeTrue()
        ->and($encoded)->not->toContain('secret-api-key-value')
        ->and($encoded)->not->toContain('FARAZSMS')
        ->and($encoded)->not->toContain('90008361');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('settings.driver', 'farazsms')
        ->where('settings.driverLabel', 'فراز اس‌ام‌اس')
        ->where('settings.driverConfigured', true)
        ->missing('settings.apiKey')
        ->missing('settings.api_key')
        ->missing('settings.sender')
        ->missing('settings.baseUrl')
        ->missing('settings.password')
        ->missing('settings.secret'));
});

test('guest cannot view admin sms logs page', function () {
    $this->get(route('admin.sms.logs.index'))->assertRedirect(route('login'));
});

test('non-admin cannot view admin sms logs page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.sms.logs.index'))
        ->assertForbidden();
});

test('admin can view admin sms logs page', function () {
    $admin = User::factory()->admin()->create();

    $message = SmsMessage::factory()->create([
        'mobile' => '09121234567',
        'message' => 'پیام نمونه برای تست',
        'type' => 'order_created',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sms.logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/sms/logs')
            ->where('logs.data.0.id', $message->id)
            ->where('logs.data.0.mobile', '09121234567')
            ->where('logs.data.0.type', 'ثبت سفارش')
            ->where('logs.data.0.typeValue', 'order_created')
            ->where('logs.data.0.messagePreview', 'پیام نمونه برای تست'));
});

test('admin sms logs page handles all sms message types', function () {
    $admin = User::factory()->admin()->create();

    foreach (SmsMessageType::cases() as $case) {
        SmsMessage::factory()->create([
            'type' => $case->value,
            'message' => 'پیام '.$case->value,
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.sms.logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/sms/logs')
            ->has('logs.data', count(SmsMessageType::cases())));
});

test('admin sms logs shows otp login type label', function () {
    $admin = User::factory()->admin()->create();

    $message = SmsMessage::factory()->create([
        'type' => SmsMessageType::OtpLogin->value,
        'message' => 'انیماتورشو: کد ورود شما 123456 است.',
        'mobile' => '09129876543',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sms.logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('logs.data.0.id', $message->id)
            ->where('logs.data.0.type', 'کد ورود')
            ->where('logs.data.0.typeValue', 'otp_login'));
});

test('admin sms logs handles unknown legacy type string without crashing', function () {
    $admin = User::factory()->admin()->create();

    $message = SmsMessage::factory()->create([
        'type' => 'legacy_unknown_type',
        'message' => 'پیام قدیمی',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sms.logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('logs.data.0.id', $message->id)
            ->where('logs.data.0.type', 'legacy_unknown_type')
            ->where('logs.data.0.typeValue', 'legacy_unknown_type'));
});

test('admin sms settings page does not include full logs list', function () {
    $admin = User::factory()->admin()->create();

    SmsMessage::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.sms.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/sms/index')
            ->missing('logs'));
});

test('admin can update sms settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.sms.settings.update'), [
            'enabled' => true,
            'admin_notifications_enabled' => false,
            'admin_mobile' => '09123456789',
        ])
        ->assertRedirect(route('admin.sms.index'));

    expect(Setting::query()->where('group', Setting::GROUP_SMS)->where('key', Setting::KEY_ENABLED)->value('value'))->toBe('true')
        ->and(Setting::query()->where('group', Setting::GROUP_SMS)->where('key', Setting::KEY_ADMIN_NOTIFICATIONS_ENABLED)->value('value'))->toBe('false')
        ->and(Setting::query()->where('group', Setting::GROUP_SMS)->where('key', Setting::KEY_ADMIN_MOBILE)->value('value'))->toBe('09123456789');
});

test('admin update sms settings rejects invalid mobile', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.sms.index'))
        ->patch(route('admin.sms.settings.update'), [
            'enabled' => true,
            'admin_notifications_enabled' => true,
            'admin_mobile' => 'invalid',
        ])
        ->assertRedirect(route('admin.sms.index'))
        ->assertSessionHasErrors(['admin_mobile']);
});

test('admin can update template body and enabled flag', function () {
    $admin = User::factory()->admin()->create();
    $template = SmsTemplate::query()->where('key', 'order_created')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('admin.sms.templates.update', $template), [
            'title' => 'عنوان جدید',
            'body' => 'متن جدید {order_number}',
            'is_enabled' => false,
        ])
        ->assertRedirect(route('admin.sms.index'));

    $template->refresh();

    expect($template->title)->toBe('عنوان جدید')
        ->and($template->body)->toBe('متن جدید {order_number}')
        ->and($template->is_enabled)->toBeFalse();
});

test('disabled global sms setting creates skipped sms messages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.sms.settings.update'), [
            'enabled' => false,
            'admin_notifications_enabled' => true,
            'admin_mobile' => '09121111111',
        ]);

    config(['sms.driver' => 'fake']);

    app(SmsService::class)->send(
        '09121234567',
        'پیام تست',
        SmsMessageType::OrderCreated,
    );

    expect(SmsMessage::query()->first()?->status)->toBe(SmsMessageStatus::Skipped);
});

test('admin notifications disabled skips admin sms messages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.sms.settings.update'), [
            'enabled' => true,
            'admin_notifications_enabled' => false,
            'admin_mobile' => '09121111111',
        ]);

    config(['sms.driver' => 'fake']);

    app(SmsService::class)->sendToAdmin(
        'پیام ادمین',
        SmsMessageType::AdminNewOrder,
    );

    expect(SmsMessage::query()->first()?->status)->toBe(SmsMessageStatus::Skipped);
});
