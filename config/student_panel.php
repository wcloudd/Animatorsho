<?php

return [

    'onboarding' => [
        'title' => 'مسیر انیماتورشو',
        'heading' => 'از اینجا شروع کن',
        'description' => 'راهنمای استفاده از پنل، ارسال تمرین و ارتباط با استاد',
        'imageUrl' => null,
        'imageAlt' => 'مسیر شروع انیماتورشو',
        'videoUrl' => null,
        'pdfUrl' => null,
        'videoGuideLabel' => 'ویدئو راهنما',
        'pdfGuideLabel' => 'دانلود راهنما',
    ],

    'sectionVisuals' => [
        'exercises' => [
            'imageUrl' => null,
            'imageAlt' => 'تصویر تمرین‌های من',
            'placeholderTitle' => 'تصویر تمرین',
            'placeholderDescription' => null,
        ],
        'mentor' => [
            'imageUrl' => null,
            'imageAlt' => 'تصویر گفتگو با استاد',
            'placeholderTitle' => 'تصویر ارتباط با استاد',
            'placeholderDescription' => null,
        ],
        'resources' => [
            'imageUrl' => null,
            'imageAlt' => 'تصویر کتابخانه تمرین',
            'placeholderTitle' => 'تصویر کتابخانه',
            'placeholderDescription' => null,
        ],
        'medals' => [
            'imageUrl' => null,
            'imageAlt' => 'تصویر مدال‌ها',
            'placeholderTitle' => 'تصویر مدال‌ها',
            'placeholderDescription' => null,
        ],
        'updates' => [
            'imageUrl' => null,
            'imageAlt' => 'تصویر آپدیت‌های دوره',
            'placeholderTitle' => 'تصویر آپدیت‌ها',
            'placeholderDescription' => null,
        ],
    ],

    'preview' => [

        'updates' => [
            [
                'id' => 'preview-update-1',
                'title' => 'به پنل هنرجوی انیماتورشو خوش آمدی',
                'summary' => 'از اینجا می‌توانی تمرین بفرستی، با استاد گفتگو کنی و منابع تمرین را دانلود کنی.',
                'type' => 'announcement',
                'typeLabel' => 'اطلاعیه',
                'publishedAtLabel' => 'امروز',
                'imageUrl' => null,
                'imageAlt' => null,
            ],
            [
                'id' => 'preview-update-2',
                'title' => 'راهنمای ارسال اولین تمرین',
                'summary' => 'بعد از تماشای جلسات در SpotPlayer، تمرین خود را از بخش تمرین‌ها ارسال کن.',
                'type' => 'exercise_update',
                'typeLabel' => 'تمرین',
                'publishedAtLabel' => 'به‌زودی',
                'imageUrl' => null,
                'imageAlt' => null,
            ],
        ],

        'resources' => [
            [
                'id' => 'preview-resource-1',
                'title' => 'راهنمای شروع سریع پنل',
                'description' => 'چک‌لیست قدم‌های اول بعد از خرید دوره',
                'type' => 'pdf',
                'typeLabel' => 'PDF',
                'imageUrl' => null,
                'imageAlt' => null,
            ],
            [
                'id' => 'preview-resource-2',
                'title' => 'فایل تمرین نمونه',
                'description' => 'یک نمونه ساده برای آشنایی با ارسال تمرین',
                'type' => 'file',
                'typeLabel' => 'فایل',
                'imageUrl' => null,
                'imageAlt' => null,
            ],
            [
                'id' => 'preview-resource-3',
                'title' => 'لینک مرجع طراحی کاراکتر',
                'description' => 'مجموعه رفرنس‌های مفید برای تمرین طراحی',
                'type' => 'link',
                'typeLabel' => 'لینک',
                'imageUrl' => null,
                'imageAlt' => null,
            ],
        ],

        'medals' => [
            'earned' => [],
            'locked' => [
                ['slug' => 'first-exercise', 'title' => 'اولین تمرین'],
                ['slug' => 'approved-exercise', 'title' => 'تمرین تاییدشده'],
                ['slug' => 'featured-exercise', 'title' => 'تمرین برگزیده'],
                ['slug' => 'persistence', 'title' => 'پشتکار'],
                ['slug' => 'revision-success', 'title' => 'اصلاح موفق'],
                ['slug' => 'creative-execution', 'title' => 'خلاقیت در اجرا'],
            ],
            'totalAvailable' => 6,
        ],

    ],

];
