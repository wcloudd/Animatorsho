<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\OrderPaymentCompletionService;
use App\Services\SpotPlayer\SpotPlayerApiProvisioningService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    config([
        'spotplayer.enabled' => false,
        'spotplayer.api_key' => null,
        'spotplayer.api_base_url' => 'https://panel.spotplayer.ir',
        'spotplayer.timeout' => 5,
        'spotplayer.test_mode' => false,
    ]);
});

/**
 * @return array{customer_name: string, customer_mobile: string}
 */
function spotPlayerTestCustomer(): array
{
    return [
        'customer_name' => 'علی رضایی',
        'customer_mobile' => '09121234567',
    ];
}

function enableSpotPlayerForTests(): void
{
    config([
        'spotplayer.enabled' => true,
        'spotplayer.api_key' => 'test-api-key-secret',
        'spotplayer.api_base_url' => 'https://panel.spotplayer.ir',
        'spotplayer.timeout' => 5,
        'spotplayer.test_mode' => true,
    ]);
}

function configurePackageSpotPlayer(CoursePackage $package, array $courseIds): CoursePackage
{
    $package->update([
        'spotplayer_course_ids' => $courseIds,
    ]);

    return $package->fresh();
}

function fakeSpotPlayerSuccess(string $licenseKey = 'SPOT-API-KEY-123', string $externalId = '5d2ee35bcddc092a304ae5eb'): void
{
    Http::fake([
        'https://panel.spotplayer.ir/license/edit/' => Http::response([
            '_id' => $externalId,
            'key' => $licenseKey,
            'url' => 'https://spotplayer.ir/license/example',
        ], 200),
    ]);
}

test('spotplayer disabled paid order creates pending license without http call', function () {
    Http::fake();

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    configurePackageSpotPlayer($package, ['course_id_ch0']);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(array_merge(['status' => OrderStatus::Pending], spotPlayerTestCustomer()));

    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
    ]);

    app(OrderPaymentCompletionService::class)->markOrderPaid($order);

    $license = SpotPlayerLicense::query()->where('order_id', $order->id)->first();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($license)->not->toBeNull()
        ->and($license->status)->toBe(SpotPlayerLicenseStatus::Pending)
        ->and($license->license_key)->toBeNull();

    Http::assertNothingSent();
});

test('spotplayer enabled with fake api success activates license and stores safe meta', function () {
    enableSpotPlayerForTests();
    fakeSpotPlayerSuccess();

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'chapter-1')->firstOrFail();
    configurePackageSpotPlayer($package, ['course_id_ch1']);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(array_merge(['status' => OrderStatus::Pending], spotPlayerTestCustomer()));

    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
    ]);

    app(OrderPaymentCompletionService::class)->markOrderPaid($order);

    $license = SpotPlayerLicense::query()->where('order_id', $order->id)->firstOrFail();

    expect($license->status)->toBe(SpotPlayerLicenseStatus::Active)
        ->and($license->license_key)->toBe('SPOT-API-KEY-123')
        ->and($license->activated_at)->not->toBeNull()
        ->and($license->meta['provisioned_via'])->toBe('api')
        ->and($license->meta['spotplayer_license_id'])->toBe('5d2ee35bcddc092a304ae5eb')
        ->and($license->meta)->not->toHaveKey('api_key')
        ->and(json_encode($license->meta))->not->toContain('test-api-key-secret');

    Http::assertSent(function (Request $request) use ($order) {
        $body = $request->data();

        return $request->url() === 'https://panel.spotplayer.ir/license/edit/'
            && $request->hasHeader('$API', 'test-api-key-secret')
            && $body['course'] === ['course_id_ch1']
            && $body['name'] === $order->customer_name
            && $body['watermark']['texts'][0]['text'] === $order->customer_mobile
            && ! array_key_exists('data', $body)
            && $body['test'] === true
            && str_contains((string) $body['payload'], (string) $order->order_number);
    });
});

test('spotplayer api failure keeps order paid and license pending with safe meta', function () {
    enableSpotPlayerForTests();

    Http::fake([
        'https://panel.spotplayer.ir/license/edit/' => Http::response([
            'message' => 'Invalid course id',
        ], 422),
    ]);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    configurePackageSpotPlayer($package, ['course_id_ch0']);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(array_merge(['status' => OrderStatus::Pending], spotPlayerTestCustomer()));

    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
    ]);

    app(OrderPaymentCompletionService::class)->markOrderPaid($order);

    $order->refresh();
    $license = SpotPlayerLicense::query()->where('order_id', $order->id)->firstOrFail();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->payments()->latest()->first()->status)->toBe(PaymentStatus::Paid)
        ->and($license->status)->toBe(SpotPlayerLicenseStatus::Pending)
        ->and($license->license_key)->toBeNull()
        ->and($license->meta['last_api_error'])->toBe('Invalid course id')
        ->and($license->meta['last_api_http_status'])->toBe(422)
        ->and($license->meta['spotplayer_error_message'])->toBe('Invalid course id')
        ->and($license->meta['spotplayer_response_keys'])->toBe(['message'])
        ->and($license->meta['spotplayer_response_preview'])->toContain('Invalid course id');
});

test('spotplayer 422 ex.msg response stores safe diagnostics without leaking api key', function () {
    enableSpotPlayerForTests();

    Http::fake([
        'https://panel.spotplayer.ir/license/edit/' => Http::response([
            'ex' => ['msg' => 'Course not found in panel'],
        ], 422),
    ]);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    configurePackageSpotPlayer($package, ['course_id_ch0']);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(array_merge(['status' => OrderStatus::Pending], spotPlayerTestCustomer()));

    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
    ]);

    app(OrderPaymentCompletionService::class)->markOrderPaid($order);

    $license = SpotPlayerLicense::query()->where('order_id', $order->id)->firstOrFail();

    expect($license->status)->toBe(SpotPlayerLicenseStatus::Pending)
        ->and($license->license_key)->toBeNull()
        ->and($license->meta['last_api_error'])->toBe('Course not found in panel')
        ->and($license->meta['last_api_http_status'])->toBe(422)
        ->and($license->meta['spotplayer_error_message'])->toBe('Course not found in panel')
        ->and($license->meta['spotplayer_response_keys'])->toBe(['ex'])
        ->and($license->meta['spotplayer_response_preview'])->toContain('Course not found in panel')
        ->and(mb_strlen((string) $license->meta['spotplayer_response_preview']))->toBeLessThanOrEqual(300)
        ->and(json_encode($license->meta))->not->toContain('test-api-key-secret');
});

test('spotplayer error response redacts api key from stored diagnostics', function () {
    enableSpotPlayerForTests();

    Http::fake([
        'https://panel.spotplayer.ir/license/edit/' => Http::response([
            'ex' => ['msg' => 'Invalid API key test-api-key-secret'],
        ], 422),
    ]);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    configurePackageSpotPlayer($package, ['course_id_ch0']);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(array_merge(['status' => OrderStatus::Pending], spotPlayerTestCustomer()));

    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
    ]);

    app(OrderPaymentCompletionService::class)->markOrderPaid($order);

    $license = SpotPlayerLicense::query()->where('order_id', $order->id)->firstOrFail();
    $encodedMeta = json_encode($license->meta);

    expect($license->status)->toBe(SpotPlayerLicenseStatus::Pending)
        ->and($encodedMeta)->not->toContain('test-api-key-secret')
        ->and($license->meta['spotplayer_error_message'])->toContain('[redacted]');
});

test('spotplayer api timeout does not break payment completion flow', function () {
    enableSpotPlayerForTests();

    Http::fake(function () {
        throw new ConnectionException('Connection timed out');
    });

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    configurePackageSpotPlayer($package, ['course_id_ch0']);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(array_merge(['status' => OrderStatus::Pending], spotPlayerTestCustomer()));

    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
    ]);

    $license = app(OrderPaymentCompletionService::class)->markOrderPaid($order);

    expect($license)->not->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($license->status)->toBe(SpotPlayerLicenseStatus::Pending)
        ->and($license->meta['last_api_error'])->toBe('Could not connect to SpotPlayer.');
});

test('missing package course ids keeps license pending with safe admin meta', function () {
    enableSpotPlayerForTests();
    Http::fake();

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $package->update([
        'spotplayer_course_ids' => null,
    ]);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create(spotPlayerTestCustomer());

    $license = SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'status' => SpotPlayerLicenseStatus::Pending,
            'license_key' => null,
        ]);

    app(SpotPlayerApiProvisioningService::class)->attemptForLicense($license);

    $license->refresh();

    expect($license->status)->toBe(SpotPlayerLicenseStatus::Pending)
        ->and($license->meta['last_api_error'])->toBe('SpotPlayer course IDs are not configured for this package.');

    Http::assertNothingSent();
});

test('retry provisioning does not duplicate active license', function () {
    enableSpotPlayerForTests();
    fakeSpotPlayerSuccess();

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    configurePackageSpotPlayer($package, ['course_id_ch0']);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create(spotPlayerTestCustomer());

    $license = SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'status' => SpotPlayerLicenseStatus::Pending,
            'license_key' => null,
        ]);

    $service = app(SpotPlayerApiProvisioningService::class);
    $service->attemptForLicense($license);
    $service->attemptForLicense($license->fresh());

    Http::assertSentCount(1);

    $license->refresh();

    expect($license->status)->toBe(SpotPlayerLicenseStatus::Active)
        ->and(SpotPlayerLicense::query()->where('order_id', $order->id)->count())->toBe(1);
});

test('manual admin activation still works and sets provisioned via manual', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    $license = SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'status' => SpotPlayerLicenseStatus::Pending,
            'license_key' => null,
        ]);

    $this->actingAs($admin)
        ->post(route('admin.licenses.activate', $license), [
            'license_key' => 'SP-MANUAL-KEY',
        ])
        ->assertRedirect();

    $license->refresh();

    expect($license->status)->toBe(SpotPlayerLicenseStatus::Active)
        ->and($license->license_key)->toBe('SP-MANUAL-KEY')
        ->and($license->meta['provisioned_via'])->toBe('manual');
});

test('revoked license key stays hidden from profile', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()->create([
        'user_id' => $user->id,
        'course_package_id' => $package->id,
        'status' => SpotPlayerLicenseStatus::Revoked,
        'license_key' => 'SP-REVOKED-KEY',
        'activated_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('profile/index')
            ->where('accessItems.0.accessState', 'license_revoked')
            ->where('accessItems.0.licenseKey', null));
});

test('admin package edit saves spotplayer course ids json array', function () {
    $admin = User::factory()->admin()->create();
    $package = CoursePackage::query()->where('slug', 'chapter-1')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('admin.packages.update', $package), [
            'title' => $package->title,
            'price_toman' => $package->price_toman,
            'is_active' => true,
            'display_order' => $package->display_order,
            'spotplayer_course_ids_input' => "course_id_ch1\ncourse_id_ch2, course_id_ch3",
        ])
        ->assertRedirect(route('admin.packages.index'));

    $package->refresh();

    expect($package->spotplayer_course_ids)->toBe([
        'course_id_ch1',
        'course_id_ch2',
        'course_id_ch3',
    ]);
});

test('existing licenses are not automatically changed when package course ids change', function () {
    enableSpotPlayerForTests();

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'chapter-1')->firstOrFail();
    configurePackageSpotPlayer($package, ['course_id_ch1']);

    $existingLicense = SpotPlayerLicense::factory()->create([
        'user_id' => $user->id,
        'course_package_id' => $package->id,
        'status' => SpotPlayerLicenseStatus::Active,
        'license_key' => 'SP-OLD-LICENSE',
        'activated_at' => now(),
        'meta' => [
            'provisioned_via' => 'manual',
        ],
    ]);

    $package->update(['spotplayer_course_ids' => ['course_id_ch1', 'course_id_ch2']]);

    Http::fake(function (Request $request) {
        expect($request->data()['course'])->toBe(['course_id_ch1', 'course_id_ch2'])
            ->and($request->data())->not->toHaveKey('data');

        return Http::response([
            '_id' => 'brand-new-license',
            'key' => 'SPOT-NEW-LICENSE',
            'url' => 'https://spotplayer.ir/license/example',
        ], 200);
    });

    $newOrder = Order::factory()
        ->for($user)
        ->forPackage($package->fresh())
        ->create(array_merge(['status' => OrderStatus::Pending], spotPlayerTestCustomer()));

    Payment::factory()->forOrder($newOrder)->create([
        'status' => PaymentStatus::Pending,
    ]);

    app(OrderPaymentCompletionService::class)->markOrderPaid($newOrder);

    $existingLicense->refresh();

    expect($existingLicense->license_key)->toBe('SP-OLD-LICENSE')
        ->and($existingLicense->meta['provisioned_via'])->toBe('manual')
        ->and(SpotPlayerLicense::query()->where('order_id', $newOrder->id)->first()?->license_key)->toBe('SPOT-NEW-LICENSE');
});

test('admin licenses page does not expose api key in props', function () {
    enableSpotPlayerForTests();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'status' => SpotPlayerLicenseStatus::Pending,
            'meta' => [
                'last_api_error' => 'Could not connect to SpotPlayer.',
                'last_api_http_status' => 500,
            ],
        ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.licenses.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/licenses/index')
            ->has('licenses.data', 1)
            ->where('licenses.data.0.apiFailureSummary', 'Could not connect to SpotPlayer.'));

    expect($response->getContent())->not->toContain('test-api-key-secret');
});

test('admin can retry pending license provisioning', function () {
    enableSpotPlayerForTests();
    fakeSpotPlayerSuccess('SPOT-RETRY-KEY');

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    configurePackageSpotPlayer($package, ['course_id_ch0']);

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create(spotPlayerTestCustomer());

    $license = SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'status' => SpotPlayerLicenseStatus::Pending,
            'license_key' => null,
            'meta' => [
                'last_api_error' => 'Previous failure',
            ],
        ]);

    $this->actingAs($admin)
        ->post(route('admin.licenses.retry-provision', $license))
        ->assertRedirect();

    $license->refresh();

    expect($license->status)->toBe(SpotPlayerLicenseStatus::Active)
        ->and($license->license_key)->toBe('SPOT-RETRY-KEY')
        ->and($license->meta['provisioned_via'])->toBe('api');
});
