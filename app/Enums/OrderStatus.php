<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case ManualReview = 'manual_review';
    case InstallmentDownPaymentPending = 'installment_down_payment_pending';
    case InstallmentDownPaymentReview = 'installment_down_payment_review';
    case InstallmentReview = 'installment_review';
    case InstallmentRejected = 'installment_rejected';
    case Cancelled = 'cancelled';
}
