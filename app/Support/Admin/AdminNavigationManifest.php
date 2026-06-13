<?php

namespace App\Support\Admin;

class AdminNavigationManifest
{
    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     items: list<array{
     *         label: string,
     *         href: string|null,
     *         route: string|null,
     *         comingSoon: bool
     *     }>
     * }>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => 'داشبورد',
                'items' => [
                    [
                        'label' => 'داشبورد',
                        'href' => route('admin.dashboard'),
                        'route' => 'admin.dashboard',
                        'comingSoon' => false,
                    ],
                ],
            ],
            [
                'key' => 'finance',
                'label' => 'مالی و فروش',
                'items' => [
                    [
                        'label' => 'سفارش‌ها',
                        'href' => route('admin.orders.index'),
                        'route' => 'admin.orders.index',
                        'comingSoon' => false,
                    ],
                    [
                        'label' => 'پرداخت‌ها',
                        'href' => route('admin.payments.index'),
                        'route' => 'admin.payments.index',
                        'comingSoon' => false,
                    ],
                    [
                        'label' => 'پیگیری اقساط',
                        'href' => route('admin.installments.index'),
                        'route' => 'admin.installments.index',
                        'comingSoon' => false,
                    ],
                    [
                        'label' => 'گزارش مالی',
                        'href' => route('admin.dashboard').'#section-finance',
                        'route' => 'admin.dashboard',
                        'comingSoon' => false,
                    ],
                ],
            ],
            [
                'key' => 'users',
                'label' => 'کاربران و دسترسی‌ها',
                'items' => [
                    [
                        'label' => 'کاربران و دسترسی‌ها',
                        'href' => route('admin.manual-enrollments.index'),
                        'route' => 'admin.manual-enrollments.index',
                        'comingSoon' => false,
                    ],
                    [
                        'label' => 'لایسنس‌ها',
                        'href' => route('admin.licenses.index'),
                        'route' => 'admin.licenses.index',
                        'comingSoon' => false,
                    ],
                ],
            ],
            [
                'key' => 'course',
                'label' => 'دوره و محتوا',
                'items' => [
                    [
                        'label' => 'بسته‌ها',
                        'href' => route('admin.packages.index'),
                        'route' => 'admin.packages.index',
                        'comingSoon' => false,
                    ],
                    [
                        'label' => 'آپدیت‌های دوره',
                        'href' => null,
                        'route' => null,
                        'comingSoon' => true,
                    ],
                    [
                        'label' => 'کتابخانه / منابع',
                        'href' => null,
                        'route' => null,
                        'comingSoon' => true,
                    ],
                ],
            ],
            [
                'key' => 'communications',
                'label' => 'ارتباطات',
                'items' => [
                    [
                        'label' => 'پشتیبانی',
                        'href' => route('admin.support.index'),
                        'route' => 'admin.support.index',
                        'comingSoon' => false,
                    ],
                    [
                        'label' => 'مشاوره‌ها',
                        'href' => route('admin.consultations.index'),
                        'route' => 'admin.consultations.index',
                        'comingSoon' => false,
                    ],
                    [
                        'label' => 'تمرین‌ها / پیام استاد',
                        'href' => null,
                        'route' => null,
                        'comingSoon' => true,
                    ],
                ],
            ],
            [
                'key' => 'security',
                'label' => 'امنیت و سیستم',
                'items' => [
                    [
                        'label' => 'رویدادهای امنیتی',
                        'href' => route('admin.security-events.index'),
                        'route' => 'admin.security-events.index',
                        'comingSoon' => false,
                    ],
                    [
                        'label' => 'پیامک',
                        'href' => route('admin.sms.index'),
                        'route' => 'admin.sms.index',
                        'comingSoon' => false,
                    ],
                ],
            ],
            [
                'key' => 'settings',
                'label' => 'تنظیمات',
                'items' => [
                    [
                        'label' => 'تنظیمات سایت',
                        'href' => route('admin.site-settings.index'),
                        'route' => 'admin.site-settings.index',
                        'comingSoon' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function groupLabels(): array
    {
        return array_map(
            fn (array $group): string => $group['label'],
            self::groups(),
        );
    }
}
