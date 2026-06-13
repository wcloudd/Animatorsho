<?php

use App\Support\JalaliDateFormatter;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('gregorian june first converts to persian khordad eleventh', function () {
    expect(JalaliDateFormatter::gregorianToJalali(2026, 6, 1))->toBe([1405, 3, 11]);
});

test('published at label uses persian jalali month names and digits', function () {
    $label = JalaliDateFormatter::publishedAtLabel(
        Carbon::parse('2026-06-01 14:30:00'),
    );

    expect($label)->toBe('۱۱ خرداد ۱۴۰۵')
        ->and($label)->not->toContain('June')
        ->and($label)->not->toContain('2026');
});

test('published at label returns dash for null', function () {
    expect(JalaliDateFormatter::publishedAtLabel(null))->toBe('—');
});

test('published at label with time uses persian jalali date and clock', function () {
    $label = JalaliDateFormatter::publishedAtLabelWithTime(
        Carbon::parse('2026-06-01 14:30:00', 'Asia/Tehran'),
    );

    expect($label)->toBe('۱۱ خرداد ۱۴۰۵، ۱۴:۳۰');
});
