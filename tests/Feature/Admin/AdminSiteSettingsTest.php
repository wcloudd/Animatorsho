<?php

use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('guest cannot view admin site settings page', function () {
    $this->get(route('admin.site-settings.index'))->assertRedirect(route('login'));
});

test('non-admin cannot view admin site settings page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.site-settings.index'))
        ->assertForbidden();
});

test('admin can view admin site settings page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.site-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/site-settings/index')
            ->has('settings')
            ->has('settings.purchasesEnabled')
            ->has('settings.maintenanceModeEnabled')
            ->has('integrations')
            ->has('integrations.zarinpalConfigured')
            ->has('integrations.farazSmsConfigured')
            ->has('integrations.spotPlayerConfigured'));
});

test('admin site settings page does not expose integration secrets', function () {
    config([
        'zarinpal.merchant_id' => 'secret-zarinpal-merchant-id',
        'sms.driver' => 'farazsms',
        'sms.providers.farazsms' => [
            'api_key' => 'secret-faraz-api-key-value',
            'sender' => '90008361',
            'base_url' => 'https://api.iranpayamak.com/ws/v1',
        ],
        'spotplayer.enabled' => true,
        'spotplayer.api_key' => 'secret-spotplayer-api-key-value',
    ]);

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.site-settings.index'))
        ->assertOk();

    $encoded = json_encode($response->original->getData()['page']['props'] ?? []);

    expect(is_string($encoded))->toBeTrue()
        ->and($encoded)->not->toContain('secret-zarinpal-merchant-id')
        ->and($encoded)->not->toContain('secret-faraz-api-key-value')
        ->and($encoded)->not->toContain('secret-spotplayer-api-key-value')
        ->and($encoded)->not->toContain('ZARINPAL_MERCHANT_ID')
        ->and($encoded)->not->toContain('SPOTPLAYER_API_KEY');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('integrations.zarinpalConfigured', true)
        ->where('integrations.farazSmsConfigured', true)
        ->where('integrations.spotPlayerConfigured', true)
        ->missing('integrations.merchantId')
        ->missing('integrations.apiKey')
        ->missing('integrations.zarinpalMerchantId')
        ->missing('integrations.spotPlayerApiKey'));
});

test('guest cannot update admin site settings', function () {
    $this->patch(route('admin.site-settings.update'), [
        'purchases_enabled' => false,
        'maintenance_mode_enabled' => true,
    ])->assertRedirect(route('login'));
});

test('non-admin cannot update admin site settings', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->patch(route('admin.site-settings.update'), [
            'purchases_enabled' => false,
            'maintenance_mode_enabled' => true,
        ])
        ->assertForbidden();
});

test('admin can update site settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.site-settings.update'), [
            'purchases_enabled' => false,
            'maintenance_mode_enabled' => true,
            'maintenance_title' => 'در حال بروزرسانی هستیم',
            'maintenance_message' => 'به‌زودی برمی‌گردیم.',
        ])
        ->assertRedirect(route('admin.site-settings.index'));

    expect(Setting::query()->where('group', Setting::GROUP_SITE)->count())->toBe(4)
        ->and(Setting::query()->where('group', Setting::GROUP_SITE)->where('key', Setting::KEY_PURCHASES_ENABLED)->value('value'))->toBe('false')
        ->and(Setting::query()->where('group', Setting::GROUP_SITE)->where('key', Setting::KEY_MAINTENANCE_MODE_ENABLED)->value('value'))->toBe('true');
});

test('integration status reflects missing env configuration', function () {
    config([
        'zarinpal.merchant_id' => null,
        'sms.driver' => 'farazsms',
        'sms.providers.farazsms' => [
            'api_key' => '',
            'sender' => '',
        ],
        'spotplayer.enabled' => false,
        'spotplayer.api_key' => null,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.site-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('integrations.zarinpalConfigured', false)
            ->where('integrations.farazSmsConfigured', false)
            ->where('integrations.spotPlayerConfigured', false));
});

test('faraz sms configured is false when only base url is set', function () {
    config([
        'sms.driver' => 'farazsms',
        'sms.providers.farazsms' => [
            'api_key' => '',
            'sender' => '',
            'base_url' => 'https://api.iranpayamak.com/ws/v1',
        ],
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.site-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('integrations.farazSmsConfigured', false));
});

test('faraz sms configured is true only when api key and sender are set', function () {
    config([
        'sms.driver' => 'log',
        'sms.providers.farazsms' => [
            'api_key' => 'secret-faraz-api-key-value',
            'sender' => '90008361',
            'base_url' => 'https://api.iranpayamak.com/ws/v1',
        ],
    ]);

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.site-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('integrations.farazSmsConfigured', true));

    $encoded = json_encode($response->original->getData()['page']['props'] ?? []);

    expect(is_string($encoded))->toBeTrue()
        ->and($encoded)->not->toContain('secret-faraz-api-key-value');
});

test('faraz sms configured is false for log driver without faraz credentials', function () {
    config([
        'sms.driver' => 'log',
        'sms.providers.farazsms' => [
            'api_key' => '',
            'sender' => '',
        ],
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.site-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('integrations.farazSmsConfigured', false));
});

test('admin site settings page shows read-only card-to-card values from config', function () {
    config([
        'card_to_card.card_number' => '6037991234567890',
        'card_to_card.card_owner_name' => 'علی رضایی',
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.site-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('cardToCard')
            ->where('cardToCard.configured', true)
            ->where('cardToCard.source', '.env / config')
            ->where('cardToCard.cardNumber', '6037991234567890')
            ->where('cardToCard.cardOwnerName', 'علی رضایی')
            ->missing('settings.cardToCardNumber')
            ->missing('settings.cardToCardOwnerName')
            ->missing('settings.cardToCardConfigured'));
});

test('admin site settings page shows placeholders when card-to-card is not configured', function () {
    config([
        'card_to_card.card_number' => null,
        'card_to_card.card_owner_name' => null,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.site-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('cardToCard.configured', false)
            ->where('cardToCard.cardNumber', 'ثبت نشده')
            ->where('cardToCard.cardOwnerName', 'ثبت نشده'));
});

test('admin cannot save card-to-card fields via site settings update', function () {
    config([
        'card_to_card.card_number' => '6037991111111111',
        'card_to_card.card_owner_name' => 'Original Owner',
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.site-settings.update'), [
            'purchases_enabled' => true,
            'maintenance_mode_enabled' => false,
            'card_to_card_number' => '6037992222222222',
            'card_to_card_owner_name' => 'Db Owner',
        ])
        ->assertRedirect(route('admin.site-settings.index'));

    expect(Setting::query()->where('group', Setting::GROUP_SITE)->where('key', 'card_to_card_number')->exists())->toBeFalse()
        ->and(Setting::query()->where('group', Setting::GROUP_SITE)->where('key', 'card_to_card_owner_name')->exists())->toBeFalse()
        ->and(config('card_to_card.card_number'))->toBe('6037991111111111')
        ->and(config('card_to_card.card_owner_name'))->toBe('Original Owner');
});
