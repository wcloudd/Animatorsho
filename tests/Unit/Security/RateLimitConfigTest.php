<?php

test('security rate limit config defines all named limiters with expected defaults', function () {
    /** @var array{rate_limits: array<string, array{max_attempts: int, decay_minutes: int}>, online_payment: array{max_retries_per_order: int}} $config */
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
            'support-ticket',
            'consultation-submit',
            'checkout-order',
            'payment-retry',
            'payment-cancel',
        ])
        ->and($rateLimits['login']['max_attempts'])->toBe(5)
        ->and($rateLimits['auth-identifier']['max_attempts'])->toBe(5)
        ->and($rateLimits['mobile-otp-send']['max_attempts'])->toBe(3)
        ->and($rateLimits['mobile-otp-verify']['max_attempts'])->toBe(10)
        ->and($rateLimits['registration-otp-send']['max_attempts'])->toBe(3)
        ->and($rateLimits['registration-otp-verify']['max_attempts'])->toBe(10)
        ->and($rateLimits['password-reset-otp-send']['max_attempts'])->toBe(3)
        ->and($rateLimits['password-reset-otp-verify']['max_attempts'])->toBe(10)
        ->and($rateLimits['password-reset-email-send']['max_attempts'])->toBe(3)
        ->and($rateLimits['password-reset-email-submit']['max_attempts'])->toBe(5)
        ->and($rateLimits['password-reset-mobile-submit']['max_attempts'])->toBe(5)
        ->and($rateLimits['support-ticket']['max_attempts'])->toBe(5)
        ->and($rateLimits['consultation-submit']['max_attempts'])->toBe(3)
        ->and($rateLimits['checkout-order']['max_attempts'])->toBe(5)
        ->and($rateLimits['payment-retry']['max_attempts'])->toBe(3)
        ->and($rateLimits['payment-cancel']['max_attempts'])->toBe(5)
        ->and($config['online_payment']['max_retries_per_order'])->toBe(5);

    foreach ($rateLimits as $limiter) {
        expect($limiter['decay_minutes'])->toBe(1);
    }
});
