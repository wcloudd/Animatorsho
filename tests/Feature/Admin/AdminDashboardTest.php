<?php

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SmsMessageStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SmsMessage;
use App\Models\SpotPlayerLicense;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);
});

test('guest cannot access admin dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin can view admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/dashboard'));
});

test('admin dashboard props include summary and queue sections', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('summary', 6)
            ->has('actionQueues')
            ->has('activityQueues')
            ->where('allActionQueuesEmpty', true)
            ->has('summary.0', fn (Assert $card) => $card
                ->has('key')
                ->has('label')
                ->has('count')
                ->has('href')
                ->has('tone')));
});

test('admin dashboard hides empty action queues and shows happy empty state', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('actionQueues', [])
            ->where('allActionQueuesEmpty', true));
});

test('global disabled skipped sms is not counted as actionable sms failure', function () {
    $admin = User::factory()->admin()->create();

    SmsMessage::factory()->skipped()->create([
        'provider' => 'log',
        'meta' => ['skip_reason' => 'global_disabled'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', fn ($summary): bool => summaryCardCountEquals($summary, 'sms_issues', 0))
            ->where('activityQueues', fn ($queues): bool => ! queueKeyExists($queues, 'recent_sms_issues')));
});

test('log provider skipped sms does not create dashboard warning', function () {
    $admin = User::factory()->admin()->create();

    SmsMessage::factory()->skipped()->create([
        'provider' => 'log',
        'meta' => ['skip_reason' => 'admin_disabled'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', fn ($summary): bool => summaryCardCountEquals($summary, 'sms_issues', 0))
            ->where('activityQueues', fn ($queues): bool => ! queueKeyExists($queues, 'recent_sms_issues')));
});

test('real failed provider sms is counted on dashboard', function () {
    $admin = User::factory()->admin()->create();

    $failedSms = SmsMessage::factory()->create([
        'status' => SmsMessageStatus::Failed,
        'provider' => 'farazsms',
        'type' => 'order_created',
        'meta' => [
            'provider_error' => 'send_rejected',
            'http_status' => 401,
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'sms_issues', 1))
            ->where('activityQueues', fn ($queues): bool => queueContainsItemId($queues, 'recent_sms_issues', $failedSms->id)));
});

test('admin dashboard exposes pending actionable items in props', function () {
    $admin = User::factory()->admin()->create();
    $cardToCardPayment = createDashboardReviewingCardToCardPayment();
    $installmentPayment = createDashboardReviewingInstallmentPayment();
    $pendingLicense = createDashboardPendingLicense($cardToCardPayment->order);
    $failedLicense = createDashboardFailedLicense();
    $openTicket = SupportTicket::factory()->open()->create([
        'subject' => 'مشکل پرداخت کارت‌به‌کارت',
    ]);
    $failedSms = SmsMessage::factory()->create([
        'status' => SmsMessageStatus::Failed,
        'provider' => 'farazsms',
        'type' => 'admin_card_to_card_review',
        'meta' => ['provider_error' => 'connection_failed'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('allActionQueuesEmpty', false)
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'pending_card_to_card', 1))
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'pending_installment', 1))
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'pending_licenses', 1))
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'license_api_failures', 1))
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'open_support_tickets', 1))
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'sms_issues', 1))
            ->where('actionQueues', fn ($queues): bool => queueContainsItemId($queues, 'pending_card_to_card', $cardToCardPayment->id))
            ->where('actionQueues', fn ($queues): bool => queueContainsItemId($queues, 'pending_installment', $installmentPayment->id))
            ->where('actionQueues', fn ($queues): bool => queueContainsItemId($queues, 'pending_licenses', $pendingLicense->id))
            ->where('actionQueues', fn ($queues): bool => queueContainsItemId($queues, 'license_api_failures', $failedLicense->id))
            ->where('actionQueues', fn ($queues): bool => queueContainsItemId($queues, 'open_support_tickets', $openTicket->id))
            ->where('actionQueues', fn ($queues): bool => ! queueKeyExists($queues, 'recent_orders'))
            ->where('activityQueues', fn ($queues): bool => queueContainsItemId($queues, 'recent_sms_issues', $failedSms->id)));
});

function summaryCardCountAtLeast(mixed $summary, string $key, int $minimum): bool
{
    return collect($summary)->contains(
        fn (array $card): bool => ($card['key'] ?? null) === $key && ($card['count'] ?? 0) >= $minimum,
    );
}

function summaryCardCountEquals(mixed $summary, string $key, int $count): bool
{
    $card = collect($summary)->firstWhere('key', $key);

    return is_array($card) && ($card['count'] ?? null) === $count;
}

function queueKeyExists(mixed $queues, string $key): bool
{
    return collect($queues)->contains(
        fn (array $queue): bool => ($queue['key'] ?? null) === $key,
    );
}

function queueContainsItemId(mixed $queues, string $key, int $id): bool
{
    $queue = collect($queues)->firstWhere('key', $key);

    if (! is_array($queue)) {
        return false;
    }

    return collect($queue['items'] ?? [])->contains(
        fn (array $item): bool => ($item['id'] ?? null) === $id,
    );
}

function createDashboardReviewingCardToCardPayment(): Payment
{
    Storage::fake('local');

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::ManualReview,
            'payment_type' => OrderPaymentType::CardToCard,
            'customer_name' => 'علی رضایی',
            'customer_mobile' => '09121234567',
        ]);

    return Payment::factory()
        ->forOrder($order)
        ->cardToCard()
        ->create()
        ->fresh(['order']);
}

function createDashboardReviewingInstallmentPayment(): Payment
{
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->installment()
        ->create([
            'customer_name' => 'سارا محمدی',
            'customer_mobile' => '09129876543',
        ]);

    return Payment::factory()
        ->forOrder($order)
        ->create([
            'method' => PaymentMethod::Installment,
            'status' => PaymentStatus::Reviewing,
        ])
        ->fresh(['order']);
}

function createDashboardPendingLicense(Order $order): SpotPlayerLicense
{
    return SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'status' => SpotPlayerLicenseStatus::Pending,
        ]);
}

function createDashboardFailedLicense(): SpotPlayerLicense
{
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    return SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'status' => SpotPlayerLicenseStatus::Failed,
            'meta' => [
                'last_api_error' => 'SpotPlayer API timeout',
            ],
        ]);
}
