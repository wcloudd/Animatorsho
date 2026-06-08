<?php

return [

    'organization' => [
        'name' => 'انیماتورشو',
        'alternate_name' => 'Nimvajabee Animatorsho',
    ],

    'default_og_image' => '/images/animatorsho-logo.svg',

    'sitemap_routes' => [
        'home',
        'consultation',
        'checkout',
    ],

    'disallow_paths' => [
        '/admin',
        '/profile',
        '/support',
        '/settings',
        '/checkout/result',
        '/checkout/confirm',
        '/checkout/zarinpal',
        '/register/verify',
        '/auth/mobile',
        '/password/mobile',
        '/reset-password',
    ],

    'noindex_route_names' => [
        'admin.*',
        'profile',
        'profile.*',
        'support.*',
        'checkout.result',
        'checkout.confirm',
        'checkout.zarinpal.callback',
        'register.verify',
        'register.verify.store',
        'register.resend-code',
        'register.change-mobile',
        'register.cancel',
        'auth.mobile.*',
        'password.mobile.*',
        'password.reset',
        'password.update',
        'security.edit',
        'user-password.update',
        'appearance.edit',
    ],

];
