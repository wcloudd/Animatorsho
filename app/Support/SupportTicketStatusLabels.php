<?php

namespace App\Support;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketStatus;

class SupportTicketStatusLabels
{
    public static function status(SupportTicketStatus $status): string
    {
        return match ($status) {
            SupportTicketStatus::Open => 'باز',
            SupportTicketStatus::Answered => 'پاسخ داده شده',
            SupportTicketStatus::WaitingUser => 'منتظر پاسخ شما',
            SupportTicketStatus::Closed => 'بسته شده',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'
     */
    public static function statusTone(SupportTicketStatus $status): string
    {
        return match ($status) {
            SupportTicketStatus::Answered => 'success',
            SupportTicketStatus::Open,
            SupportTicketStatus::WaitingUser => 'warning',
            SupportTicketStatus::Closed => 'neutral',
        };
    }

    public static function category(SupportTicketCategory $category): string
    {
        return match ($category) {
            SupportTicketCategory::Payment => 'پرداخت',
            SupportTicketCategory::License => 'لایسنس',
            SupportTicketCategory::CourseAccess => 'دسترسی دوره',
            SupportTicketCategory::Consultation => 'مشاوره',
            SupportTicketCategory::Technical => 'فنی',
            SupportTicketCategory::Other => 'سایر',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function categoryOptions(): array
    {
        return array_map(
            fn (SupportTicketCategory $category) => [
                'value' => $category->value,
                'label' => self::category($category),
            ],
            SupportTicketCategory::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function statusOptions(): array
    {
        return array_map(
            fn (SupportTicketStatus $status) => [
                'value' => $status->value,
                'label' => self::status($status),
            ],
            SupportTicketStatus::cases(),
        );
    }
}
