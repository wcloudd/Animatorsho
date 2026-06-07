<?php

use Illuminate\Support\Facades\Schema;

test('commerce tables exist after migration', function () {
    expect(Schema::hasTable('courses'))->toBeTrue()
        ->and(Schema::hasTable('course_packages'))->toBeTrue()
        ->and(Schema::hasTable('orders'))->toBeTrue()
        ->and(Schema::hasTable('payments'))->toBeTrue()
        ->and(Schema::hasTable('spot_player_licenses'))->toBeTrue()
        ->and(Schema::hasTable('settings'))->toBeTrue()
        ->and(Schema::hasTable('sms_templates'))->toBeTrue()
        ->and(Schema::hasTable('sms_messages'))->toBeTrue();
});
