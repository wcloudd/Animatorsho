<?php

$defaultProxies = in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)
    ? '*'
    : null;

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Set TRUSTED_PROXIES in .env to control which proxies are trusted for
    | X-Forwarded-* headers (Proto, Host, Port, For). Use "*" for local
    | development and HTTPS tunnels such as ngrok. In production, set this
    | to your load balancer IP(s) or leave unset on Laravel Forge / Vapor.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', $defaultProxies),

];
