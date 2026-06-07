<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case ManualReview = 'manual_review';
    case InstallmentReview = 'installment_review';
    case Cancelled = 'cancelled';
}
