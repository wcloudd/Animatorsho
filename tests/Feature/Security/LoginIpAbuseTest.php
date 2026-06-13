<?php

use App\Models\User;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    prepareAuthPageTests();
    config([
        'security.logging.enabled' => true,
        'security.logging.channel' => 'security',
        'security.login_ip_abuse.batch_window_minutes' => 60,
        'security.login_ip_abuse.batches_before_ip_lockout' => 2,
        'security.login_ip_abuse.ip_lockout_minutes' => 60,
    ]);
});

/**
 * @return object{messages: list<MessageLogged>}
 */
function captureIpAbuseLogs(): object
{
    $capture = new stdClass;
    $capture->messages = [];

    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($capture): void {
        $capture->messages[] = $event;
    });

    return $capture;
}

function exhaustLoginThrottleForMobile(string $mobile): void
{
    foreach (range(1, 5) as $attempt) {
        test()->post(route('login.store'), [
            'mobile' => $mobile,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('mobile');
    }

    test()->post(route('login.store'), [
        'mobile' => $mobile,
        'password' => 'wrong-password',
    ])->assertStatus(429);
}

test('repeated login lockout batches from same ip trigger temporary ip-level throttle', function () {
    User::factory()->withMobile('09120001111')->create();
    User::factory()->withMobile('09120002222')->create();

    exhaustLoginThrottleForMobile('09120001111');
    exhaustLoginThrottleForMobile('09120002222');

    $response = $this->post(route('login.store'), [
        'mobile' => '09120003333',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);

    expect((int) $response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(3600);
});

test('login ip abuse logs security event when ip lockout is activated', function () {
    $captured = captureIpAbuseLogs();

    User::factory()->withMobile('09123334444')->create();
    User::factory()->withMobile('09123335555')->create();

    exhaustLoginThrottleForMobile('09123334444');
    exhaustLoginThrottleForMobile('09123335555');

    $ipAbuseEvents = collect($captured->messages)
        ->filter(fn (MessageLogged $event) => $event->message === 'login_ip_abuse_triggered');

    expect($ipAbuseEvents)->toHaveCount(1)
        ->and($ipAbuseEvents->first()->context['event'])->toBe('login_ip_abuse_triggered')
        ->and($ipAbuseEvents->first()->context['throttle_type'])->toBe('ip_abuse')
        ->and($ipAbuseEvents->first()->context['batch_count'])->toBe(2)
        ->and($ipAbuseEvents->first()->context)->not->toHaveKey('password')
        ->and($ipAbuseEvents->first()->context)->not->toHaveKey('mobile')
        ->and($ipAbuseEvents->first()->context)->not->toHaveKey('email');
});

test('ip lockout blocks identifier submission route', function () {
    User::factory()->withMobile('09124446666')->create();
    User::factory()->withMobile('09124447777')->create();

    exhaustLoginThrottleForMobile('09124446666');
    exhaustLoginThrottleForMobile('09124447777');

    $this->post(route('login.identifier'), [
        'identifier' => 'new-user@example.com',
    ])->assertStatus(429);
});
