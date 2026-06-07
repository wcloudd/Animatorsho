<?php

use App\Support\TomanFormatter;

test('toman formatter uses persian digits and dot grouping', function () {
    expect(TomanFormatter::format(5_500_000))->toBe('۵.۵۰۰.۰۰۰ تومان')
        ->and(TomanFormatter::format(1_750_000))->toBe('۱.۷۵۰.۰۰۰ تومان');
});
