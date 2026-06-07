<?php

use App\Enums\SmsMessageType;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketMessageSenderType;
use App\Enums\SupportTicketStatus;
use App\Models\SmsMessage;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\Sms\SmsSettingsService;
use App\Services\Sms\SmsTemplateService;
use Database\Seeders\SmsTemplateSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);

    app(SmsSettingsService::class)->update([
        'enabled' => true,
        'admin_notifications_enabled' => true,
        'admin_mobile' => '09121111111',
    ]);
});

test('guest cannot access support index', function () {
    $this->get(route('support.index'))->assertRedirect(route('login'));
});

test('guest cannot create support ticket', function () {
    $this->post(route('support.tickets.store'), [
        'subject' => 'مشکل پرداخت',
        'category' => SupportTicketCategory::Payment->value,
        'message' => 'سلام، پرداخت من تایید نشده است.',
    ])->assertRedirect(route('login'));
});

test('authenticated user can create ticket', function () {
    $user = User::factory()->create(['name' => 'کاربر تست']);

    $this->actingAs($user)->post(route('support.tickets.store'), [
        'subject' => 'مشکل پرداخت',
        'category' => SupportTicketCategory::Payment->value,
        'message' => 'سلام، پرداخت من تایید نشده است.',
    ])->assertRedirect();

    $ticket = SupportTicket::query()->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->user_id)->toBe($user->id)
        ->and($ticket->status)->toBe(SupportTicketStatus::Open)
        ->and($ticket->customer_name)->toBe('کاربر تست')
        ->and(SupportTicketMessage::query()->count())->toBe(1)
        ->and(SupportTicketMessage::query()->first()?->sender_type)->toBe(SupportTicketMessageSenderType::User);
});

test('user sees only own tickets on support index', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    SupportTicket::factory()->forUser($owner)->create(['subject' => 'تیکت من']);
    SupportTicket::factory()->forUser($otherUser)->create(['subject' => 'تیکت دیگر']);

    $this->actingAs($owner)->get(route('support.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('support/index')
            ->has('tickets', 1)
            ->where('tickets.0.subject', 'تیکت من'));
});

test('user cannot view another users ticket', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($owner)->create();

    $this->actingAs($otherUser)->get(route('support.tickets.show', $ticket))
        ->assertForbidden();
});

test('user can reply to own open ticket', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->open()->create([
        'status' => SupportTicketStatus::Answered,
    ]);

    SupportTicketMessage::factory()->forTicket($ticket)->fromUser($user)->create();

    $this->actingAs($user)->post(route('support.tickets.messages.store', $ticket), [
        'body' => 'ممنون، منتظر بررسی هستم.',
    ])->assertRedirect();

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Open)
        ->and($ticket->messages()->count())->toBe(2);
});

test('user cannot reply to closed ticket', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->closed()->create();

    $this->actingAs($user)->post(route('support.tickets.messages.store', $ticket), [
        'body' => 'آیا می‌توانم دوباره پیام بدهم؟',
    ])->assertRedirect();

    expect($ticket->messages()->count())->toBe(0);
});

test('ticket creation creates admin sms log', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('support.tickets.store'), [
        'subject' => 'مشکل لایسنس',
        'category' => SupportTicketCategory::License->value,
        'message' => 'لایسنس SpotPlayer برای من فعال نشده است.',
    ])->assertRedirect();

    expect(
        SmsMessage::query()
            ->where('type', SmsMessageType::SupportTicketCreatedAdmin->value)
            ->exists(),
    )->toBeTrue();
});

test('sms failure does not break ticket creation', function () {
    $this->mock(SmsTemplateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('render')->andThrow(new RuntimeException('SMS exploded'));
        $mock->shouldReceive('isEnabled')->andReturn(true);
    });

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('support.tickets.store'), [
        'subject' => 'مشکل فنی',
        'category' => SupportTicketCategory::Technical->value,
        'message' => 'اپلیکیشن SpotPlayer برای من باز نمی‌شود.',
    ])->assertRedirect();

    expect(SupportTicket::query()->count())->toBe(1)
        ->and(SupportTicketMessage::query()->count())->toBe(1);
});

test('sms failure does not break user reply', function () {
    $this->mock(SmsTemplateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('render')->andThrow(new RuntimeException('SMS exploded'));
        $mock->shouldReceive('isEnabled')->andReturn(true);
    });

    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->open()->create();

    $this->actingAs($user)->post(route('support.tickets.messages.store', $ticket), [
        'body' => 'پیگیری می‌کنم.',
    ])->assertRedirect();

    expect($ticket->messages()->count())->toBe(1);
});
