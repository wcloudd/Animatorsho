<?php

test('the security event prune command is registered on the scheduler', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('security:prune-events')
        ->assertSuccessful();
});
