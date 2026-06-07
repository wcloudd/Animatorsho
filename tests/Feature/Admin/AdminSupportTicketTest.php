<?php

use App\Enums\SmsMessageStatus;
use App\Enums\SmsMessageType;
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

test('guest cannot access admin support', function () {
    $this->get(route('admin.support.index'))->assertRedirect(route('login'));
});

test('non-admin cannot access admin support', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('admin.support.index'))->assertForbidden();
});

test('admin can list all tickets', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    SupportTicket::factory()->forUser($owner)->create(['subject' => 'تیکت اول']);
    SupportTicket::factory()->forUser($otherUser)->create(['subject' => 'تیکت دوم']);

    $this->actingAs($admin)->get(route('admin.support.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/support/index')
            ->has('tickets.data', 2));
});

test('admin can view ticket detail', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->create();

    SupportTicketMessage::factory()->forTicket($ticket)->fromUser($user)->create([
        'body' => 'پیام اول کاربر',
    ]);

    $this->actingAs($admin)->get(route('admin.support.show', $ticket))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/support/show')
            ->where('ticket.id', $ticket->id)
            ->has('messages', 1));
});

test('admin can reply to ticket', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->open()->create();

    $this->actingAs($admin)->post(route('admin.support.messages.store', $ticket), [
        'body' => 'سلام، در حال بررسی هستیم.',
        'waiting_for_user' => false,
    ])->assertRedirect();

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Answered)
        ->and($ticket->messages()->count())->toBe(1);
});

test('admin reply with waiting flag sets waiting user status', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->open()->create();

    $this->actingAs($admin)->post(route('admin.support.messages.store', $ticket), [
        'body' => 'لطفاً رسید را ارسال کنید.',
        'waiting_for_user' => true,
    ])->assertRedirect();

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::WaitingUser);
});

test('admin can close and reopen ticket', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->open()->create();

    $this->actingAs($admin)->post(route('admin.support.close', $ticket))
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Closed)
        ->and($ticket->fresh()->closed_at)->not->toBeNull();

    $this->actingAs($admin)->post(route('admin.support.reopen', $ticket))
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Open)
        ->and($ticket->fresh()->closed_at)->toBeNull();
});

test('admin reply creates user sms log', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create([
        'customer_mobile' => '09121234567',
    ]);

    $this->actingAs($admin)->post(route('admin.support.messages.store', $ticket), [
        'body' => 'پاسخ پشتیبانی برای شما ثبت شد.',
    ])->assertRedirect();

    expect(
        SmsMessage::query()
            ->where('type', SmsMessageType::SupportTicketRepliedUser->value)
            ->exists(),
    )->toBeTrue();
});

test('missing mobile logs skipped sms and does not break admin reply', function () {
    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->withoutMobile()->create();

    $this->actingAs($admin)->post(route('admin.support.messages.store', $ticket), [
        'body' => 'پاسخ پشتیبانی.',
    ])->assertRedirect();

    expect($ticket->messages()->count())->toBe(1)
        ->and(
            SmsMessage::query()
                ->where('type', SmsMessageType::SupportTicketRepliedUser->value)
                ->where('status', SmsMessageStatus::Skipped)
                ->exists(),
        )->toBeTrue();
});

test('sms failure does not break admin reply', function () {
    $this->mock(SmsTemplateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('render')->andThrow(new RuntimeException('SMS exploded'));
        $mock->shouldReceive('isEnabled')->andReturn(true);
    });

    $admin = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->create([
        'customer_mobile' => '09121234567',
    ]);

    $this->actingAs($admin)->post(route('admin.support.messages.store', $ticket), [
        'body' => 'پاسخ پشتیبانی.',
    ])->assertRedirect();

    expect($ticket->messages()->count())->toBe(1);
});

test('admin support filters by status', function () {
    $admin = User::factory()->admin()->create();

    SupportTicket::factory()->open()->create(['subject' => 'باز']);
    SupportTicket::factory()->closed()->create(['subject' => 'بسته']);

    $this->actingAs($admin)->get(route('admin.support.index', ['status' => 'closed']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tickets.data', 1)
            ->where('tickets.data.0.subject', 'بسته'));
});
