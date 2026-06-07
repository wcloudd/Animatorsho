<?php

test('detects https behind a trusted reverse proxy', function () {
    $response = $this->call('GET', '/', [], [], [], [
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'tunnel.example.test',
        'HTTP_X_FORWARDED_PORT' => '443',
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTPS' => 'off',
    ]);

    $response->assertOk();

    expect(request()->isSecure())->toBeTrue();
    expect(request()->getScheme())->toBe('https');
});

test('home page does not emit http asset urls when accessed via https proxy', function () {
    $response = $this->call('GET', '/', [], [], [], [
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'tunnel.example.test',
        'HTTP_X_FORWARDED_PORT' => '443',
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTPS' => 'off',
    ]);

    $response->assertOk();

    $content = $response->getContent();

    expect($content)->not->toMatch('/src="http:\/\//');
    expect($content)->not->toMatch('/href="http:\/\/tunnel\.example\.test/');
});
