<?php

use App\Enums\ConsultationRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SupportTicketCategory;
use App\Models\ConsultationRequest;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Database\Seeders\SmsTemplateSeeder;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

beforeEach(function () {
    $this->withoutVite();
    config([
        'security.logging.enabled' => true,
        'security.logging.channel' => 'security',
        'security.honeypot.enabled' => true,
        'security.honeypot.field_name' => 'preferred_contact_window',
    ]);
});

/**
 * @return object{messages: list<MessageLogged>}
 */
function captureSecurityEventLogs(): object
{
    $capture = new stdClass;
    $capture->messages = [];

    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($capture): void {
        $capture->messages[] = $event;
    });

    return $capture;
}

/**
 * @return array<string, string>
 */
function securityLogInertiaHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'text/html, application/xhtml+xml',
    ];
}

function securityLogHoneypotFieldName(): string
{
    return (string) config('security.honeypot.field_name');
}

test('honeypot rejection logs security event without honeypot value', function () {
    $captured = captureSecurityEventLogs();

    $user = User::factory()->withMobile('09121112222')->create();

    $this->actingAs($user)
        ->from(route('consultation'))
        ->post(route('consultation.store'), [
            'full_name' => 'علی رضایی',
            securityLogHoneypotFieldName() => 'bot-value',
        ])
        ->assertRedirect(route('consultation'));

    expect($captured->messages)->toHaveCount(1)
        ->and($captured->messages[0]->message)->toBe('honeypot_triggered')
        ->and($captured->messages[0]->context['event'])->toBe('honeypot_triggered')
        ->and($captured->messages[0]->context['route'])->toBe('consultation.store')
        ->and($captured->messages[0]->context)->not->toHaveKey('preferred_contact_window');
});

test('inertia auth rate limit logs auth rate limit exceeded event', function () {
    prepareAuthPageTests();
    $captured = captureSecurityEventLogs();

    User::factory()->withMobile('09121234567')->create();

    $this->get(route('login'));

    foreach (range(1, 5) as $attempt) {
        $this->withHeaders(securityLogInertiaHeaders())
            ->post(route('login.store'), [
                'mobile' => '09121234567',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('mobile');
    }

    $this->withHeaders(securityLogInertiaHeaders())
        ->from(route('login'))
        ->post(route('login.store'), [
            'mobile' => '09121234567',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('throttle');

    expect($captured->messages)->toHaveCount(1)
        ->and($captured->messages[0]->message)->toBe('auth_rate_limit_exceeded')
        ->and($captured->messages[0]->context['event'])->toBe('auth_rate_limit_exceeded')
        ->and($captured->messages[0]->context['route'])->toBe('login.store')
        ->and($captured->messages[0]->context['limiter'])->toBe('login')
        ->and($captured->messages[0]->context)->toHaveKey('retry_after_seconds')
        ->and($captured->messages[0]->context)->not->toHaveKey('mobile')
        ->and($captured->messages[0]->context)->not->toHaveKey('password');
});

test('payment retry ceiling logs security event with safe payment metadata', function () {
    $this->seed(AnimatorshoCourseSeeder::class);
    $captured = captureSecurityEventLogs();

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::Pending,
        ]);

    $payment = Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
        'amount_toman' => $order->final_amount_toman,
        'meta' => [
            'retry_count' => 5,
            'authority' => 'A00000000000000000000000000000000000',
        ],
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('request');
    });

    $this->actingAs($user)
        ->post(route('profile.orders.retry-online-payment', $order))
        ->assertRedirect(route('profile'))
        ->assertSessionHas('error');

    expect($captured->messages)->toHaveCount(1)
        ->and($captured->messages[0]->message)->toBe('payment_retry_ceiling_reached')
        ->and($captured->messages[0]->context['event'])->toBe('payment_retry_ceiling_reached')
        ->and($captured->messages[0]->context['order_id'])->toBe($order->id)
        ->and($captured->messages[0]->context['payment_id'])->toBe($payment->id)
        ->and($captured->messages[0]->context['retry_count'])->toBe(5)
        ->and($captured->messages[0]->context['max_retries'])->toBe(5)
        ->and($captured->messages[0]->context)->not->toHaveKey('authority')
        ->and($captured->messages[0]->context)->not->toHaveKey('ref_id');
});

test('consultation duplicate blocked logs security event with open request id', function () {
    $captured = captureSecurityEventLogs();

    $user = User::factory()->withMobile('09121112233')->create();
    $openRequest = ConsultationRequest::factory()->forUser($user)->withStatus(ConsultationRequestStatus::New)->create();

    $this->actingAs($user)
        ->post(route('consultation.store'), [
            'full_name' => 'کاربر تست',
        ])
        ->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->count())->toBe(1);

    expect($captured->messages)->toHaveCount(1)
        ->and($captured->messages[0]->message)->toBe('consultation_duplicate_blocked')
        ->and($captured->messages[0]->context['event'])->toBe('consultation_duplicate_blocked')
        ->and($captured->messages[0]->context['open_consultation_request_id'])->toBe($openRequest->id)
        ->and($captured->messages[0]->context['user_id'])->toBe($user->id)
        ->and($captured->messages[0]->context)->not->toHaveKey('mobile');
});

test('support open ticket cap logs security event with counts', function () {
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);
    $captured = captureSecurityEventLogs();

    $user = User::factory()->withMobile()->create();

    SupportTicket::factory()->forUser($user)->open()->count(3)->create();

    $this->actingAs($user)->post(route('support.tickets.store'), [
        'subject' => 'تیکت چهارم',
        'category' => SupportTicketCategory::Payment->value,
        'message' => 'این تیکت نباید ثبت شود.',
    ])->assertRedirect();

    expect(SupportTicket::query()->count())->toBe(3);

    expect($captured->messages)->toHaveCount(1)
        ->and($captured->messages[0]->message)->toBe('support_open_ticket_cap_reached')
        ->and($captured->messages[0]->context['event'])->toBe('support_open_ticket_cap_reached')
        ->and($captured->messages[0]->context['open_ticket_count'])->toBe(3)
        ->and($captured->messages[0]->context['max_open_tickets'])->toBe(3)
        ->and($captured->messages[0]->context['user_id'])->toBe($user->id);
});

test('successful consultation submission does not log security event', function () {
    $captured = captureSecurityEventLogs();

    $user = User::factory()->withMobile('09124445566')->create();

    $this->actingAs($user)
        ->post(route('consultation.store'), [
            'full_name' => 'علی رضایی',
        ])
        ->assertRedirect(route('consultation'));

    expect($captured->messages)->toBeEmpty();
});
