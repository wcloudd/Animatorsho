<?php

namespace App\Support;

use App\Enums\ConsultationRequestStatus;

class ConsultationRequestStatusLabels
{
    public static function status(ConsultationRequestStatus $status): string
    {
        return match ($status) {
            ConsultationRequestStatus::New => 'جدید',
            ConsultationRequestStatus::Contacted => 'تماس گرفته شد',
            ConsultationRequestStatus::FollowUp => 'نیازمند پیگیری',
            ConsultationRequestStatus::Converted => 'منجر به خرید شد',
            ConsultationRequestStatus::Cancelled => 'کنسل شد',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function statusTone(ConsultationRequestStatus $status): string
    {
        return match ($status) {
            ConsultationRequestStatus::New => 'warning',
            ConsultationRequestStatus::Contacted => 'neutral',
            ConsultationRequestStatus::FollowUp => 'warning',
            ConsultationRequestStatus::Converted => 'success',
            ConsultationRequestStatus::Cancelled => 'danger',
        };
    }

    public static function level(?string $level): ?string
    {
        return match ($level) {
            'beginner' => 'کاملاً مبتدی',
            'some-design' => 'کمی طراحی بلدم',
            'made-animation' => 'قبلاً انیمیشن ساختم',
            'unsure' => 'مطمئن نیستم',
            default => null,
        };
    }

    public static function interest(?string $interest): ?string
    {
        return match ($interest) {
            'full-course' => 'دوره جامع انیماتورشو',
            'chapter' => 'خرید فصل جداگانه',
            'installment' => 'خرید اقساطی',
            'summer-class' => 'کلاس تابستان',
            'advice-only' => 'فقط مشاوره می‌خوام',
            default => null,
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function statusOptions(): array
    {
        return array_map(
            fn (ConsultationRequestStatus $status) => [
                'value' => $status->value,
                'label' => self::status($status),
            ],
            ConsultationRequestStatus::cases(),
        );
    }
}
