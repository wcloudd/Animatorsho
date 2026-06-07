<?php

use App\Support\IranianMobile;

test('normalizes standard iranian mobile formats', function (string $input, string $expected) {
    expect(IranianMobile::normalize($input))->toBe($expected);
})->with([
    ['09123456789', '09123456789'],
    ['+989123456789', '09123456789'],
    ['989123456789', '09123456789'],
    ['9123456789', '09123456789'],
    ['0912 345 6789', '09123456789'],
    ['0912-345-6789', '09123456789'],
]);

test('returns null for invalid mobile numbers', function (string $input) {
    expect(IranianMobile::normalize($input))->toBeNull();
})->with([
    ['08123456789'],
    ['0912345678'],
    ['091234567890'],
    [''],
    ['abc'],
]);

test('isValid returns true only for valid numbers', function () {
    expect(IranianMobile::isValid('09123456789'))->toBeTrue()
        ->and(IranianMobile::isValid('invalid'))->toBeFalse();
});
