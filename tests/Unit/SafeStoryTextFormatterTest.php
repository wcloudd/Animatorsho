<?php

use App\Support\SafeStoryTextFormatter;

test('safe story text formatter strips html tags on sanitize', function () {
    expect(SafeStoryTextFormatter::sanitize('<script>alert(1)</script>سلام'))
        ->toBe('alert(1)سلام');
});

test('safe story text formatter renders bold and lists safely', function () {
    $html = SafeStoryTextFormatter::toHtml("**عنوان**\n- مورد اول\n- مورد دوم");

    expect($html)
        ->toContain('<strong>عنوان</strong>')
        ->toContain('<ul>')
        ->toContain('<li>مورد اول</li>')
        ->not->toContain('<script>');
});

test('safe story text formatter escapes raw html in output', function () {
    $html = SafeStoryTextFormatter::toHtml('<img src=x onerror=alert(1)>');

    expect($html)
        ->toContain('&lt;img')
        ->not->toContain('<img');
});

test('safe story text formatter builds compact preview', function () {
    $preview = SafeStoryTextFormatter::toPreview("**داستان**\n- صحنه اول\n- صحنه دوم", 20);

    expect($preview)->toBe('داستان صحنه اول صحنه…');
});
