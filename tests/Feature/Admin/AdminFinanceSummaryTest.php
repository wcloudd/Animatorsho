<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\Admin\AdminFinanceSummaryService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);
});

test('paid payments count as confirmed revenue', function () {
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $user = User::factory()->create();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create(['final_amount_toman' => 5_000_000]);

    createFinancePayment($order, paid: true, attributes: [
        'method' => PaymentMethod::Zarinpal,
        'paid_at' => now(),
    ]);

    $summary = app(AdminFinanceSummaryService::class)->forDashboard();

    expect($summary['confirmedRevenueTotal'])->toBe(5_000_000)
        ->and($summary['successfulPaymentsCount'])->toBe(1)
        ->and($summary['paidByMethod'])->toHaveCount(1)
        ->and($summary['paidByMethod'][0]['method'])->toBe('zarinpal')
        ->and($summary['paidByMethod'][0]['amountToman'])->toBe(5_000_000);
});

test('reviewing and pending payments do not count as confirmed revenue', function () {
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $user = User::factory()->create();

    $paidOrder = Order::factory()->for($user)->forPackage($package)->paid()->create([
        'final_amount_toman' => 1_000_000,
    ]);
    createFinancePayment($paidOrder, paid: true, attributes: [
        'method' => PaymentMethod::Zarinpal,
        'paid_at' => now(),
    ]);

    $reviewingOrder = Order::factory()->for($user)->forPackage($package)->create([
        'status' => OrderStatus::ManualReview,
        'final_amount_toman' => 2_000_000,
    ]);
    createFinancePayment($reviewingOrder, attributes: [
        'method' => PaymentMethod::CardToCard,
        'status' => PaymentStatus::Reviewing,
    ]);

    $pendingOrder = Order::factory()->for($user)->forPackage($package)->create([
        'final_amount_toman' => 3_000_000,
    ]);
    createFinancePayment($pendingOrder, attributes: [
        'status' => PaymentStatus::Pending,
    ]);

    $summary = app(AdminFinanceSummaryService::class)->forDashboard();

    expect($summary['confirmedRevenueTotal'])->toBe(1_000_000)
        ->and($summary['pendingPaymentsCount'])->toBe(2)
        ->and($summary['reviewingCardToCardCount'])->toBe(1)
        ->and($summary['reviewingCardToCardAmount'])->toBe(2_000_000);
});

test('failed payments do not count as confirmed revenue', function () {
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->forPackage($package)->create([
        'status' => OrderStatus::Failed,
    ]);

    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Failed,
        'amount_toman' => 4_000_000,
    ]);

    $summary = app(AdminFinanceSummaryService::class)->forDashboard();

    expect($summary['confirmedRevenueTotal'])->toBe(0)
        ->and($summary['failedOrCancelledCount'])->toBe(1);
});

test('installment reviewing payments appear separately from revenue', function () {
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->forPackage($package)->installment()->create([
        'final_amount_toman' => 1_500_000,
    ]);

    createFinancePayment($order, attributes: [
        'method' => PaymentMethod::Installment,
        'status' => PaymentStatus::Reviewing,
    ]);

    $summary = app(AdminFinanceSummaryService::class)->forDashboard();

    expect($summary['confirmedRevenueTotal'])->toBe(0)
        ->and($summary['reviewingInstallmentCount'])->toBe(1)
        ->and($summary['reviewingInstallmentAmount'])->toBe(1_500_000);
});

test('external paid grants are tracked separately from online revenue totals', function () {
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $user = User::factory()->create();

    $onlineOrder = Order::factory()->for($user)->forPackage($package)->paid()->create([
        'final_amount_toman' => 2_000_000,
    ]);
    createFinancePayment($onlineOrder, paid: true, attributes: [
        'method' => PaymentMethod::Zarinpal,
        'paid_at' => now(),
    ]);

    $externalOrder = Order::factory()->for($user)->forPackage($package)->paid()->create([
        'final_amount_toman' => 800_000,
    ]);
    createFinancePayment($externalOrder, paid: true, attributes: [
        'method' => PaymentMethod::External,
        'paid_at' => now(),
    ]);

    $summary = app(AdminFinanceSummaryService::class)->forDashboard();

    expect($summary['confirmedRevenueTotal'])->toBe(2_800_000)
        ->and($summary['externalGrantsCount'])->toBe(1)
        ->and($summary['externalGrantsAmount'])->toBe(800_000);

    $externalRow = collect($summary['paidByMethod'])->firstWhere('method', 'external');
    expect($externalRow)->not->toBeNull()
        ->and($externalRow['amountToman'])->toBe(800_000);
});

test('today and current month revenue use paid_at boundaries', function () {
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $user = User::factory()->create();

    $todayOrder = Order::factory()->for($user)->forPackage($package)->paid()->create([
        'final_amount_toman' => 700_000,
    ]);
    createFinancePayment($todayOrder, paid: true, attributes: [
        'paid_at' => now(),
    ]);

    $oldMonthOrder = Order::factory()->for($user)->forPackage($package)->paid()->create([
        'final_amount_toman' => 300_000,
    ]);
    createFinancePayment($oldMonthOrder, paid: true, attributes: [
        'paid_at' => now()->subMonths(2),
    ]);

    $summary = app(AdminFinanceSummaryService::class)->forDashboard();

    expect($summary['confirmedRevenueToday'])->toBe(700_000)
        ->and($summary['confirmedRevenueCurrentMonth'])->toBe(700_000)
        ->and($summary['confirmedRevenueTotal'])->toBe(1_000_000);
});

test('top packages are ranked by confirmed paid revenue', function () {
    $fullPackage = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $chapterPackage = CoursePackage::query()->where('slug', 'chapter-1')->firstOrFail();
    $user = User::factory()->create();

    foreach ([[$fullPackage, 3_000_000], [$chapterPackage, 1_000_000], [$fullPackage, 2_000_000]] as [$package, $amount]) {
        $order = Order::factory()->for($user)->forPackage($package)->paid()->create([
            'final_amount_toman' => $amount,
        ]);
        createFinancePayment($order, paid: true, attributes: [
            'paid_at' => now(),
        ]);
    }

    $summary = app(AdminFinanceSummaryService::class)->forDashboard();
    $top = $summary['topPackages'];

    expect($top)->not->toBeEmpty()
        ->and($top[0]['packageId'])->toBe($fullPackage->id)
        ->and($top[0]['revenueToman'])->toBe(5_000_000)
        ->and($top[0]['paidCount'])->toBe(2);
});

test('admin dashboard exposes finance summary props and labels', function () {
    $admin = User::factory()->admin()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->forPackage($package)->paid()->create([
        'final_amount_toman' => 1_200_000,
    ]);

    createFinancePayment($order, paid: true, attributes: [
        'method' => PaymentMethod::Zarinpal,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('financeSummary')
            ->has('financeSummary.confirmedRevenueTotalFormatted')
            ->has('financeSummary.paidByMethod')
            ->has('financeSummary.topPackages')
            ->where('financeSummary.confirmedRevenueTotal', 1_200_000)
            ->where('financeSummary.successfulPaymentsCount', 1));
});

test('active licenses count is separate from registrations', function () {
    $admin = User::factory()->admin()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->forPackage($package)->paid()->create();

    SpotPlayerLicense::factory()->forOrder($order)->create([
        'status' => SpotPlayerLicenseStatus::Active,
    ]);

    User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('financeSummary.activeLicensesCount', 1)
            ->where('activityMetrics', fn ($metrics): bool => collect($metrics)->contains(
                fn (array $card): bool => ($card['key'] ?? null) === 'registrations_today' && ($card['count'] ?? 0) >= 1,
            )));
});

/**
 * @param  array<string, mixed>  $attributes
 */
function createFinancePayment(Order $order, bool $paid = false, array $attributes = []): Payment
{
    $factory = Payment::factory()->forOrder($order);

    if ($paid) {
        $factory = $factory->paid();
    }

    $payment = $factory->create($attributes);

    $payment->update(['amount_toman' => $order->final_amount_toman]);

    return $payment->fresh();
}
