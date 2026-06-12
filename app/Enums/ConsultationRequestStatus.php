<?php

namespace App\Enums;

enum ConsultationRequestStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case FollowUp = 'follow_up';
    case Converted = 'converted';
    case Cancelled = 'cancelled';

    public function isOpen(): bool
    {
        return match ($this) {
            self::New, self::Contacted, self::FollowUp => true,
            self::Converted, self::Cancelled => false,
        };
    }

    /**
     * @return list<self>
     */
    public static function openCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status) => $status->isOpen(),
        ));
    }
}
