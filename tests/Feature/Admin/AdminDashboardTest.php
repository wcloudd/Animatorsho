<?php

use App\Enums\ConsultationRequestStatus;
use App\Enums\ExerciseSubmissionStatus;
use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SmsMessageStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Enums\SupportTicketStatus;
use App\Models\ConsultationRequest;
use App\Models\CoursePackage;
use App\Models\ExerciseSubmission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SecurityEvent;
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
            ->has('activityMetrics', 2)
            ->has('securityEventsLast24Hours')
            ->has('summary', 10)
            ->has('actionQueues')
            ->has('activityQueues')
            ->has('financeSummary')
            ->where('allActionQueuesEmpty', true)
            ->has('activityMetrics.0', fn (Assert $card) => $card
                ->has('key')
                ->has('label')
                ->has('count')
                ->where('href', null)
                ->has('tone'))
            ->has('summary.0', fn (Assert $card) => $card
                ->has('key')
                ->has('label')
                ->has('count')
                ->has('href')
                ->has('tone')));
});

test('admin pages share grouped navigation for admin users', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('adminNavGroups', 7)
            ->where('adminNavGroups.0.label', 'داشبورد')
            ->where('adminNavGroups.1.label', 'مالی و فروش')
            ->where('adminNavGroups.2.label', 'کاربران و دسترسی‌ها')
            ->where('adminNavGroups.3.label', 'دوره و محتوا')
            ->where('adminNavGroups.4.label', 'ارتباطات')
            ->where('adminNavGroups.5.label', 'امنیت و سیستم')
            ->where('adminNavGroups.6.label', 'تنظیمات'));
});

test('admin navigation keeps important existing links visible in groups', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(function (Assert $page): void {
            $groups = $page->toArray()['props']['adminNavGroups'] ?? [];
            $items = collect($groups)->flatMap(fn (array $group): array => $group['items'] ?? []);
            $labels = collect($groups)->pluck('label');

            expect($labels->all())->toBe([
                'داشبورد',
                'مالی و فروش',
                'کاربران و دسترسی‌ها',
                'دوره و محتوا',
                'ارتباطات',
                'امنیت و سیستم',
                'تنظیمات',
            ]);
            expect($items->firstWhere('route', 'admin.orders.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.payments.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.installments.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.manual-enrollments.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.licenses.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.packages.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.course-updates.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.course-resources.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.support.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.consultations.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.exercise-submissions.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.exercise-attachments.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.security-events.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.sms.index'))->not->toBeNull();
            expect($items->firstWhere('route', 'admin.site-settings.index'))->not->toBeNull();
            expect($items)->toHaveCount(16);
        });
});

test('guest does not receive admin navigation groups', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('adminNavGroups', null));
});

test('admin dashboard exposes security events count for last 24 hours', function () {
    $admin = User::factory()->admin()->create();

    SecurityEvent::factory()->create([
        'occurred_at' => now()->subHours(2),
    ]);
    SecurityEvent::factory()->create([
        'occurred_at' => now()->subDays(2),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('securityEventsLast24Hours', 1));
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
            ->where('actionQueues', fn ($queues): bool => queueItemHrefContainsFocus($queues, 'pending_card_to_card', $cardToCardPayment->id))
            ->where('actionQueues', fn ($queues): bool => queueContainsItemId($queues, 'pending_installment', $installmentPayment->id))
            ->where('actionQueues', fn ($queues): bool => queueItemHrefContains(
                $queues,
                'pending_installment',
                route('admin.installments.index', [
                    'status' => 'awaiting_review',
                    'focus' => $installmentPayment->order_id,
                ]),
            ))
            ->where('actionQueues', fn ($queues): bool => queueContainsItemId($queues, 'pending_licenses', $pendingLicense->id))
            ->where('actionQueues', fn ($queues): bool => queueItemHrefContainsFocus($queues, 'pending_licenses', $pendingLicense->id))
            ->where('actionQueues', fn ($queues): bool => queueContainsItemId($queues, 'license_api_failures', $failedLicense->id))
            ->where('actionQueues', fn ($queues): bool => queueItemHrefContainsFocus($queues, 'license_api_failures', $failedLicense->id))
            ->where('actionQueues', fn ($queues): bool => queueContainsItemId($queues, 'open_support_tickets', $openTicket->id))
            ->where('actionQueues', fn ($queues): bool => queueItemHrefContains($queues, 'open_support_tickets', route('admin.support.show', $openTicket)))
            ->where('actionQueues', fn ($queues): bool => ! queueKeyExists($queues, 'recent_orders'))
            ->where('activityQueues', fn ($queues): bool => queueContainsItemId($queues, 'recent_sms_issues', $failedSms->id)));
});

test('admin dashboard shows registration metrics', function () {
    $admin = User::factory()->admin()->create([
        'created_at' => now()->subDays(20),
    ]);

    User::factory()->create(['created_at' => now()]);
    User::factory()->create(['created_at' => now()->startOfDay()]);
    User::factory()->create(['created_at' => now()->subDays(3)]);
    User::factory()->create(['created_at' => now()->subDays(8)]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activityMetrics', fn ($metrics): bool => summaryCardCountEquals($metrics, 'registrations_today', 2))
            ->where('activityMetrics', fn ($metrics): bool => summaryCardCountEquals($metrics, 'registrations_last_7_days', 3)));
});

test('admin dashboard shows consultation metrics', function () {
    $admin = User::factory()->admin()->create();

    ConsultationRequest::factory()->count(2)->withStatus(ConsultationRequestStatus::New)->create();
    ConsultationRequest::factory()->withStatus(ConsultationRequestStatus::FollowUp)->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', fn ($summary): bool => summaryCardCountEquals($summary, 'new_consultations', 2))
            ->where('summary', fn ($summary): bool => summaryCardCountEquals($summary, 'follow_up_consultations', 1))
            ->where('actionQueues', fn ($queues): bool => queueKeyExists($queues, 'new_consultations'))
            ->where('actionQueues', fn ($queues): bool => queueKeyExists($queues, 'follow_up_consultations')));
});

test('admin dashboard shows support ticket status metrics', function () {
    $admin = User::factory()->admin()->create();

    SupportTicket::factory()->open()->create();
    SupportTicket::factory()->create([
        'status' => SupportTicketStatus::WaitingUser,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', fn ($summary): bool => summaryCardCountEquals($summary, 'open_support_tickets', 1))
            ->where('summary', fn ($summary): bool => summaryCardCountEquals($summary, 'support_waiting_user', 1)));
});

test('admin dashboard shows pending payment installment and license counts', function () {
    $admin = User::factory()->admin()->create();

    createDashboardReviewingCardToCardPayment();
    createDashboardReviewingInstallmentPayment();
    createDashboardFailedLicense();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'pending_card_to_card', 1))
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'pending_installment', 1))
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'license_api_failures', 1)));
});

test('admin dashboard shows zero pending exercise submissions when none exist', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', fn ($summary): bool => summaryCardCountEquals($summary, 'exercise_submissions_pending', 0))
            ->where('actionQueues', fn ($queues): bool => ! queueKeyExists($queues, 'pending_exercise_submissions')));
});

test('admin dashboard shows pending exercise submission count and queue', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create(['name' => 'هنرجوی تست']);

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین اول',
        'status' => ExerciseSubmissionStatus::Submitted,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', fn ($summary): bool => summaryCardCountAtLeast($summary, 'exercise_submissions_pending', 1))
            ->where('actionQueues', fn ($queues): bool => queueKeyExists($queues, 'pending_exercise_submissions'))
            ->where('actionQueues', fn ($queues): bool => queueContainsItemId($queues, 'pending_exercise_submissions', $submission->id)));
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

function queueItemHrefContainsFocus(mixed $queues, string $key, int $id): bool
{
    $queue = collect($queues)->firstWhere('key', $key);

    if (! is_array($queue)) {
        return false;
    }

    $item = collect($queue['items'] ?? [])->firstWhere('id', $id);

    if (! is_array($item)) {
        return false;
    }

    $href = $item['href'] ?? '';

    return is_string($href)
        && str_contains($href, 'focus='.$id);
}

function queueItemHrefContains(mixed $queues, string $key, string $href): bool
{
    $queue = collect($queues)->firstWhere('key', $key);

    if (! is_array($queue)) {
        return false;
    }

    return collect($queue['items'] ?? [])->contains(
        fn (array $item): bool => ($item['href'] ?? null) === $href,
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
