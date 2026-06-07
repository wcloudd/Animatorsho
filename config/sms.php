<?php

return [

    'driver' => env('SMS_DRIVER', 'log'),

    'defaults' => [
        'enabled' => filter_var(env('SMS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'admin_notifications_enabled' => true,
        'admin_mobile' => env('SMS_ADMIN_MOBILE'),
    ],

    'providers' => [
        'farazsms' => [
            'api_key' => env('FARAZSMS_API_KEY'),
            'sender' => env('FARAZSMS_SENDER'),
            'base_url' => env('FARAZSMS_BASE_URL', 'https://api.iranpayamak.com/ws/v1'),
        ],
        'kavenegar' => [
            'api_key' => env('KAVENEGAR_API_KEY'),
            'sender' => env('KAVENEGAR_SENDER'),
        ],
        'melipayamak' => [
            'username' => env('MELIPAYAMAK_USERNAME'),
            'password' => env('MELIPAYAMAK_PASSWORD'),
            'from' => env('MELIPAYAMAK_FROM'),
        ],
    ],

    'templates' => [
        'otp_login' => [
            'title' => 'کد ورود',
            'body' => 'انیماتورشو: کد ورود شما {code} است.',
            'description' => 'code',
        ],
        'order_created' => [
            'title' => 'ثبت سفارش',
            'body' => 'انیماتورشو: سفارش {order_number} ثبت شد. مبلغ: {amount} تومان.',
            'description' => 'order_number, amount',
        ],
        'payment_paid' => [
            'title' => 'تأیید پرداخت',
            'body' => 'انیماتورشو: پرداخت سفارش {order_number} تأیید شد.',
            'description' => 'order_number',
        ],
        'card_to_card_submitted' => [
            'title' => 'دریافت رسید',
            'body' => 'انیماتورشو: رسید سفارش {order_number} دریافت شد. پس از بررسی اطلاع می‌دهیم.',
            'description' => 'order_number',
        ],
        'card_to_card_approved' => [
            'title' => 'تأیید کارت‌به‌کارت',
            'body' => 'انیماتورشو: پرداخت کارت‌به‌کارت سفارش {order_number} تأیید شد.',
            'description' => 'order_number',
        ],
        'card_to_card_rejected' => [
            'title' => 'رد پرداخت',
            'body' => 'انیماتورشو: پرداخت سفارش {order_number} تأیید نشد. {note}',
            'description' => 'order_number, note',
        ],
        'license_activated' => [
            'title' => 'فعال‌سازی لایسنس',
            'body' => 'انیماتورشو: لایسنس {package} فعال شد. از پروفایل مشاهده کنید.',
            'description' => 'package',
        ],
        'admin_new_order' => [
            'title' => 'سفارش جدید (ادمین)',
            'body' => 'سفارش جدید {order_number} — {customer_name} — {amount} تومان',
            'description' => 'order_number, customer_name, amount',
        ],
        'admin_card_to_card_review' => [
            'title' => 'بررسی رسید (ادمین)',
            'body' => 'رسید جدید برای بررسی: سفارش {order_number} — {customer_mobile}',
            'description' => 'order_number, customer_mobile',
        ],
        'installment_request_submitted' => [
            'title' => 'ثبت درخواست اقساطی',
            'body' => 'انیماتورشو: درخواست خرید اقساطی {order_number} ثبت شد. پشتیبانی برای هماهنگی با شما تماس می‌گیرد.',
            'description' => 'order_number',
        ],
        'admin_installment_review' => [
            'title' => 'درخواست اقساطی (ادمین)',
            'body' => 'درخواست اقساطی جدید: {order_number} — {customer_name} — {requested_term}',
            'description' => 'order_number, customer_name, requested_term',
        ],
        'installment_rejected' => [
            'title' => 'رد درخواست اقساطی',
            'body' => 'انیماتورشو: درخواست اقساطی سفارش {order_number} تأیید نشد. {note}',
            'description' => 'order_number, note',
        ],
        'support_ticket_created_admin' => [
            'title' => 'تیکت پشتیبانی (ادمین)',
            'body' => 'تیکت جدید: {subject} — {customer_name} — {category}',
            'description' => 'subject, customer_name, category',
        ],
        'support_ticket_replied_user' => [
            'title' => 'پاسخ پشتیبانی',
            'body' => 'انیماتورشو: پاسخ جدید برای «{subject}». از بخش پشتیبانی مشاهده کنید.',
            'description' => 'subject',
        ],
    ],

];
