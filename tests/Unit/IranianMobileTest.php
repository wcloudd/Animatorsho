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

test('validationMessage returns empty message for blank input', function (?string $input) {
    expect(IranianMobile::validationMessage($input))->toBe(IranianMobile::EMPTY_MESSAGE);
})->with([
    [null],
    [''],
    ['   '],
]);

test('validationMessage returns invalid characters message for letters or symbols without digits', function (string $input) {
    expect(IranianMobile::validationMessage($input))->toBe(IranianMobile::INVALID_CHARACTERS_MESSAGE);
})->with([
    ['09abc456789'],
    ['abc'],
    ['***'],
    ['09@123456789'],
]);

test('validationMessage returns too many digits message for long 09 numbers', function (string $input) {
    expect(IranianMobile::validationMessage($input))->toBe(IranianMobile::TOO_MANY_DIGITS_MESSAGE);
})->with([
    ['091234567890'],
    ['091234567891'],
    ['09 123 456 789 0'],
]);

test('validationMessage returns too few digits message for short 09 numbers', function (string $input) {
    expect(IranianMobile::validationMessage($input))->toBe(IranianMobile::TOO_FEW_DIGITS_MESSAGE);
})->with([
    ['0912345678'],
    ['091234567'],
]);

test('validationMessage returns wrong prefix message for 11-digit numbers not starting with 09', function (string $input) {
    expect(IranianMobile::validationMessage($input))->toBe(IranianMobile::WRONG_PREFIX_MESSAGE);
})->with([
    ['08123456789'],
    ['07123456789'],
]);

test('validationMessage returns generic fallback for other invalid numbers', function (string $input) {
    expect(IranianMobile::validationMessage($input))->toBe(IranianMobile::GENERIC_MESSAGE);
})->with([
    ['12345'],
    ['912345678'],
]);
