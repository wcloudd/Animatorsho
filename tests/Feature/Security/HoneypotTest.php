<?php

use App\Enums\SupportTicketCategory;
use App\Models\ConsultationRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Database\Seeders\SmsTemplateSeeder;

beforeEach(function () {
    $this->withoutVite();
    config([
        'security.honeypot.enabled' => true,
        'security.honeypot.field_name' => 'preferred_contact_window',
    ]);
});

function honeypotFieldName(): string
{
    return (string) config('security.honeypot.field_name');
}

function honeypotConsultationPayload(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'علی رضایی',
    ], $overrides);
}

function honeypotSupportTicketPayload(array $overrides = []): array
{
    return array_merge([
        'subject' => 'مشکل پرداخت',
        'category' => SupportTicketCategory::Payment->value,
        'message' => 'سلام، پرداخت من تایید نشده است.',
    ], $overrides);
}

test('consultation submission passes when honeypot field is missing', function () {
    $user = User::factory()->withMobile('09121112222')->create();

    $this->actingAs($user)
        ->from(route('consultation'))
        ->post(route('consultation.store'), honeypotConsultationPayload())
        ->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->count())->toBe(1);
});

test('consultation submission passes when honeypot field is empty', function () {
    $user = User::factory()->withMobile('09123334455')->create();

    $this->actingAs($user)
        ->from(route('consultation'))
        ->post(route('consultation.store'), honeypotConsultationPayload([
            honeypotFieldName() => '',
        ]))
        ->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->count())->toBe(1);
});

test('consultation submission is rejected when honeypot field is filled', function () {
    $user = User::factory()->withMobile('09124445566')->create();

    $this->actingAs($user)
        ->from(route('consultation'))
        ->post(route('consultation.store'), honeypotConsultationPayload([
            honeypotFieldName() => 'bot-value',
        ]))
        ->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->count())->toBe(0);
});

test('support ticket creation passes when honeypot field is missing', function () {
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)
        ->from(route('support.index'))
        ->post(route('support.tickets.store'), honeypotSupportTicketPayload())
        ->assertRedirect();

    expect(SupportTicket::query()->count())->toBe(1);
});

test('support ticket creation passes when honeypot field is empty', function () {
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)
        ->from(route('support.index'))
        ->post(route('support.tickets.store'), honeypotSupportTicketPayload([
            honeypotFieldName() => '',
        ]))
        ->assertRedirect();

    expect(SupportTicket::query()->count())->toBe(1);
});

test('support ticket creation is rejected when honeypot field is filled', function () {
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);

    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)
        ->from(route('support.index'))
        ->post(route('support.tickets.store'), honeypotSupportTicketPayload([
            honeypotFieldName() => 'bot-value',
        ]))
        ->assertRedirect(route('support.index'));

    expect(SupportTicket::query()->count())->toBe(0);
});

test('support ticket reply passes when honeypot field is missing', function () {
    $user = User::factory()->withMobile()->create();
    $ticket = SupportTicket::factory()->forUser($user)->open()->create();

    $this->actingAs($user)
        ->from(route('support.tickets.show', $ticket))
        ->post(route('support.tickets.messages.store', $ticket), [
            'body' => 'پیام پیگیری',
        ])
        ->assertRedirect();

    expect(SupportTicketMessage::query()->count())->toBe(1);
});

test('support ticket reply passes when honeypot field is empty', function () {
    $user = User::factory()->withMobile()->create();
    $ticket = SupportTicket::factory()->forUser($user)->open()->create();

    $this->actingAs($user)
        ->from(route('support.tickets.show', $ticket))
        ->post(route('support.tickets.messages.store', $ticket), [
            'body' => 'پیام پیگیری',
            honeypotFieldName() => '',
        ])
        ->assertRedirect();

    expect(SupportTicketMessage::query()->count())->toBe(1);
});

test('support ticket reply is rejected when honeypot field is filled', function () {
    $user = User::factory()->withMobile()->create();
    $ticket = SupportTicket::factory()->forUser($user)->open()->create();

    $this->actingAs($user)
        ->from(route('support.tickets.show', $ticket))
        ->post(route('support.tickets.messages.store', $ticket), [
            'body' => 'پیام پیگیری',
            honeypotFieldName() => 'bot-value',
        ])
        ->assertRedirect(route('support.tickets.show', $ticket));

    expect(SupportTicketMessage::query()->count())->toBe(0);
});

test('disabled honeypot config allows submissions even when field is filled', function () {
    config(['security.honeypot.enabled' => false]);

    $user = User::factory()->withMobile('09125556677')->create();

    $this->actingAs($user)
        ->from(route('consultation'))
        ->post(route('consultation.store'), honeypotConsultationPayload([
            honeypotFieldName() => 'bot-value',
        ]))
        ->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->count())->toBe(1);
});
