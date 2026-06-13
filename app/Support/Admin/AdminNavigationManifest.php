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
     *         href: string,
     *         route: string
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
                    ],
                    [
                        'label' => 'پرداخت‌ها',
                        'href' => route('admin.payments.index'),
                        'route' => 'admin.payments.index',
                    ],
                    [
                        'label' => 'پیگیری اقساط',
                        'href' => route('admin.installments.index'),
                        'route' => 'admin.installments.index',
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
                    ],
                    [
                        'label' => 'لایسنس‌ها',
                        'href' => route('admin.licenses.index'),
                        'route' => 'admin.licenses.index',
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
                    ],
                    [
                        'label' => 'مشاوره‌ها',
                        'href' => route('admin.consultations.index'),
                        'route' => 'admin.consultations.index',
                    ],
                ],
            ],
            [
                'key' => 'security',
                'label' => 'امنیت و سیستم',
                'items' => [
                    [
                        'label' => 'امنیت',
                        'href' => route('admin.security-events.index'),
                        'route' => 'admin.security-events.index',
                    ],
                    [
                        'label' => 'پیامک',
                        'href' => route('admin.sms.index'),
                        'route' => 'admin.sms.index',
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
