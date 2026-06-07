<?php

use App\Support\InstallmentPricing;

test('one month term adds 500,000 surcharge and 40 percent down payment', function () {
    $pricing = InstallmentPricing::calculate(5_500_000, 'one_month');

    expect($pricing)->toMatchArray([
        'term' => 'one_month',
        'months' => 1,
        'down_payment_percent' => 40,
        'cash_price_toman' => 5_500_000,
        'extra_amount_toman' => 500_000,
        'installment_total_toman' => 6_000_000,
        'down_payment_toman' => 2_400_000,
        'remaining_toman' => 3_600_000,
    ]);
});

test('two months term adds 1,000,000 surcharge and 40 percent down payment', function () {
    $pricing = InstallmentPricing::calculate(5_500_000, 'two_months');

    expect($pricing)->toMatchArray([
        'term' => 'two_months',
        'months' => 2,
        'down_payment_percent' => 40,
        'cash_price_toman' => 5_500_000,
        'extra_amount_toman' => 1_000_000,
        'installment_total_toman' => 6_500_000,
        'down_payment_toman' => 2_600_000,
        'remaining_toman' => 3_900_000,
    ]);
});

test('remaining is always total minus down payment with rounding', function () {
    $pricing = InstallmentPricing::calculate(5_555_555, 'one_month');

    $expectedTotal = 5_555_555 + 500_000;
    $expectedDown = (int) round($expectedTotal * 40 / 100);

    expect($pricing['installment_total_toman'])->toBe($expectedTotal)
        ->and($pricing['down_payment_toman'])->toBe($expectedDown)
        ->and($pricing['remaining_toman'])->toBe($expectedTotal - $expectedDown)
        ->and($pricing['down_payment_toman'] + $pricing['remaining_toman'])->toBe($expectedTotal);
});

test('unknown term throws', function () {
    InstallmentPricing::calculate(5_500_000, 'three_months');
})->throws(InvalidArgumentException::class);

test('available terms come from config', function () {
    expect(InstallmentPricing::availableTerms())->toBe(['one_month', 'two_months']);
});
