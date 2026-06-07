<?php

namespace App\Enums;

enum SmsMessageType: string
{
    case OrderCreated = 'order_created';
    case PaymentPaid = 'payment_paid';
    case CardToCardSubmitted = 'card_to_card_submitted';
    case CardToCardApproved = 'card_to_card_approved';
    case CardToCardRejected = 'card_to_card_rejected';
    case LicenseActivated = 'license_activated';
    case AdminNewOrder = 'admin_new_order';
    case AdminCardToCardReview = 'admin_card_to_card_review';
    case InstallmentRequestSubmitted = 'installment_request_submitted';
    case AdminInstallmentReview = 'admin_installment_review';
    case InstallmentRejected = 'installment_rejected';
    case SupportTicketCreatedAdmin = 'support_ticket_created_admin';
    case SupportTicketRepliedUser = 'support_ticket_replied_user';
    case OtpLogin = 'otp_login';

    /**
     * @return list<self>
     */
    public static function seededTypes(): array
    {
        return [
            self::OtpLogin,
            self::OrderCreated,
            self::PaymentPaid,
            self::CardToCardSubmitted,
            self::CardToCardApproved,
            self::CardToCardRejected,
            self::LicenseActivated,
            self::AdminNewOrder,
            self::AdminCardToCardReview,
            self::InstallmentRequestSubmitted,
            self::AdminInstallmentReview,
            self::InstallmentRejected,
            self::SupportTicketCreatedAdmin,
            self::SupportTicketRepliedUser,
        ];
    }

    public function isAdminType(): bool
    {
        return in_array($this, [
            self::AdminNewOrder,
            self::AdminCardToCardReview,
            self::AdminInstallmentReview,
            self::SupportTicketCreatedAdmin,
        ], true);
    }
}
