<?php

namespace App\Support;

use App\Enums\SmsMessageStatus;
use App\Enums\SmsMessageType;

class SmsStatusLabels
{
    public static function status(SmsMessageStatus $status): string
    {
        return match ($status) {
            SmsMessageStatus::Pending => 'در صف',
            SmsMessageStatus::Sent => 'ارسال شده',
            SmsMessageStatus::Failed => 'ناموفق',
            SmsMessageStatus::Skipped => 'رد شده',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'
     */
    public static function statusTone(SmsMessageStatus $status): string
    {
        return match ($status) {
            SmsMessageStatus::Sent => 'success',
            SmsMessageStatus::Pending => 'warning',
            SmsMessageStatus::Failed,
            SmsMessageStatus::Skipped => 'neutral',
        };
    }

    public static function type(SmsMessageType $type): string
    {
        return match ($type) {
            SmsMessageType::OrderCreated => 'ثبت سفارش',
            SmsMessageType::PaymentPaid => 'تأیید پرداخت',
            SmsMessageType::CardToCardSubmitted => 'دریافت رسید',
            SmsMessageType::CardToCardApproved => 'تأیید کارت‌به‌کارت',
            SmsMessageType::CardToCardRejected => 'رد پرداخت',
            SmsMessageType::LicenseActivated => 'فعال‌سازی لایسنس',
            SmsMessageType::AdminNewOrder => 'سفارش جدید (ادمین)',
            SmsMessageType::AdminCardToCardReview => 'بررسی رسید (ادمین)',
            SmsMessageType::InstallmentRequestSubmitted => 'ثبت درخواست اقساطی',
            SmsMessageType::AdminInstallmentReview => 'درخواست اقساطی (ادمین)',
            SmsMessageType::InstallmentRejected => 'رد درخواست اقساطی',
            SmsMessageType::SupportTicketCreatedAdmin => 'تیکت پشتیبانی (ادمین)',
            SmsMessageType::SupportTicketRepliedUser => 'پاسخ پشتیبانی',
            SmsMessageType::OtpLogin => 'کد ورود',
            default => self::unknownTypeLabel($type->value),
        };
    }

    public static function unknownTypeLabel(?string $typeValue = null): string
    {
        if (is_string($typeValue) && $typeValue !== '') {
            return $typeValue;
        }

        return 'نوع نامشخص';
    }
}
