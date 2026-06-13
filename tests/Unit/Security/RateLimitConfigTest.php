<?php

test('security rate limit config defines all named limiters with expected defaults', function () {
    /** @var array{rate_limits: array<string, array{max_attempts: int, decay_minutes: int}>, online_payment: array{max_retries_per_order: int}, login_ip_abuse: array{batch_window_minutes: int, batches_before_ip_lockout: int, ip_lockout_minutes: int}} $config */
    $config = require dirname(__DIR__, 3).'/config/security.php';
    $rateLimits = $config['rate_limits'];

    expect($rateLimits)->toBeArray()
        ->and($rateLimits)->toHaveKeys([
            'login',
            'auth-identifier',
            'mobile-otp-send',
            'mobile-otp-verify',
            'registration-otp-send',
            'registration-otp-verify',
            'password-reset-otp-send',
            'password-reset-otp-verify',
            'password-reset-email-send',
            'password-reset-email-submit',
            'password-reset-mobile-submit',
            'support-ticket-create',
            'support-ticket-reply',
            'consultation-submit',
            'checkout-order',
            'payment-retry',
            'payment-cancel',
        ])
        ->and($rateLimits['login']['max_attempts'])->toBe(5)
        ->and($rateLimits['login']['decay_minutes'])->toBe(20)
        ->and($rateLimits['auth-identifier']['max_attempts'])->toBe(5)
        ->and($rateLimits['auth-identifier']['decay_minutes'])->toBe(20)
        ->and($rateLimits['mobile-otp-send']['max_attempts'])->toBe(3)
        ->and($rateLimits['mobile-otp-verify']['max_attempts'])->toBe(5)
        ->and($rateLimits['mobile-otp-verify']['decay_minutes'])->toBe(20)
        ->and($rateLimits['registration-otp-send']['max_attempts'])->toBe(3)
        ->and($rateLimits['registration-otp-verify']['max_attempts'])->toBe(10)
        ->and($rateLimits['password-reset-otp-send']['max_attempts'])->toBe(3)
        ->and($rateLimits['password-reset-otp-verify']['max_attempts'])->toBe(10)
        ->and($rateLimits['password-reset-email-send']['max_attempts'])->toBe(3)
        ->and($rateLimits['password-reset-email-submit']['max_attempts'])->toBe(5)
        ->and($rateLimits['password-reset-mobile-submit']['max_attempts'])->toBe(5)
        ->and($rateLimits['support-ticket-create']['max_attempts'])->toBe(3)
        ->and($rateLimits['support-ticket-reply']['max_attempts'])->toBe(10)
        ->and($rateLimits['consultation-submit']['max_attempts'])->toBe(3)
        ->and($rateLimits['checkout-order']['max_attempts'])->toBe(5)
        ->and($rateLimits['payment-retry']['max_attempts'])->toBe(3)
        ->and($rateLimits['payment-cancel']['max_attempts'])->toBe(5)
        ->and($config['online_payment']['max_retries_per_order'])->toBe(5)
        ->and($config['support']['max_open_tickets'])->toBe(3)
        ->and($config['login_ip_abuse']['batch_window_minutes'])->toBe(60)
        ->and($config['login_ip_abuse']['batches_before_ip_lockout'])->toBe(2)
        ->and($config['login_ip_abuse']['ip_lockout_minutes'])->toBe(60);

    foreach ($rateLimits as $name => $limiter) {
        if (in_array($name, ['login', 'auth-identifier', 'mobile-otp-verify'], true)) {
            expect($limiter['decay_minutes'])->toBe(20);

            continue;
        }

        expect($limiter['decay_minutes'])->toBe(1);
    }
});

test('security config defines honeypot defaults', function () {
    /** @var array{honeypot: array{enabled: bool, field_name: string, message: string}} $config */
    $config = require dirname(__DIR__, 3).'/config/security.php';

    expect($config['honeypot'])->toBeArray()
        ->and($config['honeypot']['enabled'])->toBeTrue()
        ->and($config['honeypot']['field_name'])->toBe('preferred_contact_window')
        ->and($config['honeypot']['message'])->toBe('در ارسال فرم مشکلی پیش آمد. لطفاً دوباره تلاش کنید.');
});

test('security config defines logging defaults', function () {
    /** @var array{logging: array{enabled: bool, channel: string, user_agent_max_length: int, database_enabled: bool, database_retention_days: int}} $config */
    $config = require dirname(__DIR__, 3).'/config/security.php';

    expect($config['logging'])->toBeArray()
        ->and($config['logging']['enabled'])->toBeTrue()
        ->and($config['logging']['channel'])->toBe('security')
        ->and($config['logging']['user_agent_max_length'])->toBe(200)
        ->and($config['logging']['database_enabled'])->toBeTrue()
        ->and($config['logging']['database_retention_days'])->toBe(90);
});
