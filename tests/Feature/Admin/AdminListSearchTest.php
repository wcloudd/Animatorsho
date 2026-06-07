<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SmsMessage;
use App\Models\SpotPlayerLicense;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

test('admin orders search filters by order number', function () {
    Order::factory()->create(['order_number' => 'ORD-SEARCH-111']);
    Order::factory()->create(['order_number' => 'ORD-OTHER-222']);

    $this->actingAs($this->admin)
        ->get(route('admin.orders.index', ['q' => 'SEARCH-111']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.orderNumber', 'ORD-SEARCH-111')
            ->where('filters.q', 'SEARCH-111'));
});

test('admin orders search combines with status filter', function () {
    Order::factory()->create([
        'order_number' => 'ORD-COMBINED-001',
        'status' => OrderStatus::Paid,
    ]);
    Order::factory()->create([
        'order_number' => 'ORD-COMBINED-002',
        'status' => OrderStatus::Pending,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.orders.index', [
            'status' => OrderStatus::Paid->value,
            'q' => 'COMBINED',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.orderNumber', 'ORD-COMBINED-001'));
});

test('admin payments search filters by tracking code', function () {
    Payment::factory()->create(['tracking_code' => 'TRACK-UNIQUE-999']);
    Payment::factory()->create(['tracking_code' => 'TRACK-OTHER-000']);

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index', ['q' => 'UNIQUE-999']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)
            ->where('payments.data.0.trackingCode', 'TRACK-UNIQUE-999'));
});

test('admin licenses search filters by license key', function () {
    SpotPlayerLicense::factory()->active()->create([
        'license_key' => 'LICENSE-KEY-ABC123',
    ]);
    SpotPlayerLicense::factory()->active()->create([
        'license_key' => 'LICENSE-KEY-XYZ789',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.licenses.index', ['q' => 'ABC123']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('licenses.data', 1)
            ->where('licenses.data.0.licenseKey', 'LICENSE-KEY-ABC123'));
});

test('admin support search filters by subject', function () {
    SupportTicket::factory()->create(['subject' => 'مشکل پرداخت خاص']);
    SupportTicket::factory()->create(['subject' => 'سوال دیگر']);

    $this->actingAs($this->admin)
        ->get(route('admin.support.index', ['q' => 'پرداخت خاص']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tickets.data', 1)
            ->where('tickets.data.0.subject', 'مشکل پرداخت خاص'));
});

test('admin support search filters by message body', function () {
    $ticket = SupportTicket::factory()->create(['subject' => 'تیکت عمومی']);
    SupportTicketMessage::factory()->forTicket($ticket)->create([
        'body' => 'متن پیام منحصربه‌فرد برای جستجو',
    ]);
    SupportTicket::factory()->create(['subject' => 'تیکت دیگر']);

    $this->actingAs($this->admin)
        ->get(route('admin.support.index', ['q' => 'منحصربه‌فرد']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tickets.data', 1)
            ->where('tickets.data.0.subject', 'تیکت عمومی'));
});

test('admin sms logs search filters by mobile', function () {
    SmsMessage::factory()->create(['mobile' => '09121112233']);
    SmsMessage::factory()->create(['mobile' => '09334445566']);

    $this->actingAs($this->admin)
        ->get(route('admin.sms.logs.index', ['q' => '0912111']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.mobile', '09121112233'));
});

test('admin list search ignores single character query', function () {
    Order::factory()->create(['order_number' => 'ORD-SINGLE-X']);

    $this->actingAs($this->admin)
        ->get(route('admin.orders.index', ['q' => 'X']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.q', null)
            ->has('orders.data', 1));
});

test('admin list search ignores empty query', function () {
    Order::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.orders.index', ['q' => '']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.q', null)
            ->has('orders.data', 2));
});

test('admin licenses focus filter shows only targeted license', function () {
    $target = SpotPlayerLicense::factory()->active()->create();
    SpotPlayerLicense::factory()->active()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.licenses.index', ['focus' => $target->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('licenses.data', 1)
            ->where('licenses.data.0.id', $target->id)
            ->where('filters.focus', $target->id));
});

test('admin payments focus filter shows only targeted payment', function () {
    $target = Payment::factory()->create();
    Payment::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index', ['focus' => $target->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)
            ->where('payments.data.0.id', $target->id)
            ->where('filters.focus', $target->id));
});
