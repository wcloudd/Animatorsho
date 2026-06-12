<?php

namespace App\Support;

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;

class ProfileStatusLabels
{
    public static function orderStatus(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'در انتظار پرداخت',
            OrderStatus::Paid => 'پرداخت موفق',
            OrderStatus::Failed => 'ناموفق',
            OrderStatus::ManualReview => 'در انتظار بررسی کارت‌به‌کارت',
            OrderStatus::InstallmentDownPaymentPending => 'در انتظار پرداخت پیش‌پرداخت',
            OrderStatus::InstallmentReview => 'در انتظار بررسی اقساطی',
            OrderStatus::InstallmentRejected => 'درخواست اقساط رد شد',
            OrderStatus::Cancelled => 'لغو شده',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'
     */
    public static function orderStatusTone(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Paid => 'success',
            OrderStatus::Pending,
            OrderStatus::ManualReview,
            OrderStatus::InstallmentDownPaymentPending,
            OrderStatus::InstallmentReview => 'warning',
            OrderStatus::Failed,
            OrderStatus::InstallmentRejected,
            OrderStatus::Cancelled => 'neutral',
        };
    }

    public static function paymentType(OrderPaymentType $paymentType): string
    {
        return match ($paymentType) {
            OrderPaymentType::Cash => 'پرداخت نقدی',
            OrderPaymentType::Installment => 'خرید اقساطی',
            OrderPaymentType::CardToCard => 'کارت‌به‌کارت',
            OrderPaymentType::External => 'خرید خارج از سایت',
        };
    }

    public static function paymentStatus(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Pending => 'در انتظار پرداخت',
            PaymentStatus::Paid => 'پرداخت موفق',
            PaymentStatus::Failed => 'ناموفق',
            PaymentStatus::Reviewing => 'در حال بررسی',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'
     */
    public static function paymentStatusTone(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Paid => 'success',
            PaymentStatus::Pending,
            PaymentStatus::Reviewing => 'warning',
            PaymentStatus::Failed => 'neutral',
        };
    }

    public static function paymentMethod(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::Zarinpal => 'پرداخت آنلاین (زرین‌پال)',
            PaymentMethod::CardToCard => 'کارت‌به‌کارت',
            PaymentMethod::Installment => 'اقساطی',
            PaymentMethod::External => 'ثبت دستی / خارج از سایت',
        };
    }

    public static function licenseStatus(SpotPlayerLicenseStatus $status): string
    {
        return match ($status) {
            SpotPlayerLicenseStatus::Pending => 'در انتظار فعال‌سازی',
            SpotPlayerLicenseStatus::Active => 'فعال',
            SpotPlayerLicenseStatus::Failed => 'ناموفق',
            SpotPlayerLicenseStatus::Revoked => 'غیرفعال',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'
     */
    public static function licenseStatusTone(SpotPlayerLicenseStatus $status): string
    {
        return match ($status) {
            SpotPlayerLicenseStatus::Active => 'success',
            SpotPlayerLicenseStatus::Pending => 'warning',
            SpotPlayerLicenseStatus::Failed,
            SpotPlayerLicenseStatus::Revoked => 'neutral',
        };
    }
}
