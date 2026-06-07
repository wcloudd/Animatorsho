<?php

namespace App\Services\Sms;

use App\Enums\PaymentMethod;
use App\Enums\SmsMessageType;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Models\SupportTicket;
use App\Support\InstallmentTermLabels;
use App\Support\SupportTicketStatusLabels;
use App\Support\TomanFormatter;
use Illuminate\Support\Facades\Log;

class SmsNotifier
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly SmsTemplateService $templates,
    ) {}

    public function notifyOrderCreated(Order $order): void
    {
        $this->safely(function () use ($order): void {
            $order->loadMissing('coursePackage');
            $context = $this->orderContext($order);

            $this->sms->send(
                $order->customer_mobile ?? '',
                $this->templates->render(SmsMessageType::OrderCreated, $context),
                SmsMessageType::OrderCreated,
                $this->orderMeta($order),
            );

            $this->sms->sendToAdmin(
                $this->templates->render(SmsMessageType::AdminNewOrder, $context),
                SmsMessageType::AdminNewOrder,
                $this->orderMeta($order),
            );
        }, 'order_created', $order->id);
    }

    public function notifyInstallmentRequestSubmitted(Order $order): void
    {
        $this->safely(function () use ($order): void {
            $order->loadMissing(['coursePackage', 'payments']);
            $context = $this->installmentContext($order);

            $this->sms->send(
                $order->customer_mobile ?? '',
                $this->templates->render(SmsMessageType::InstallmentRequestSubmitted, $context),
                SmsMessageType::InstallmentRequestSubmitted,
                $this->orderMeta($order),
            );

            $this->sms->sendToAdmin(
                $this->templates->render(SmsMessageType::AdminInstallmentReview, $context),
                SmsMessageType::AdminInstallmentReview,
                $this->orderMeta($order),
            );
        }, 'installment_request_submitted', $order->id);
    }

    public function notifyCardToCardSubmitted(Order $order): void
    {
        $this->safely(function () use ($order): void {
            $context = $this->orderContext($order);

            $this->sms->send(
                $order->customer_mobile ?? '',
                $this->templates->render(SmsMessageType::CardToCardSubmitted, $context),
                SmsMessageType::CardToCardSubmitted,
                $this->orderMeta($order),
            );
        }, 'card_to_card_submitted', $order->id);
    }

    public function notifyAdminCardToCardReview(Order $order): void
    {
        $this->safely(function () use ($order): void {
            $context = $this->orderContext($order);

            $this->sms->sendToAdmin(
                $this->templates->render(SmsMessageType::AdminCardToCardReview, $context),
                SmsMessageType::AdminCardToCardReview,
                $this->orderMeta($order),
            );
        }, 'admin_card_to_card_review', $order->id);
    }

    public function notifyPaymentPaid(Order $order, Payment $payment): void
    {
        $this->safely(function () use ($order, $payment): void {
            $context = $this->orderContext($order);
            $type = $payment->method === PaymentMethod::CardToCard
                ? SmsMessageType::CardToCardApproved
                : SmsMessageType::PaymentPaid;

            $this->sms->send(
                $order->customer_mobile ?? '',
                $this->templates->render($type, $context),
                $type,
                array_merge($this->orderMeta($order), ['payment_id' => $payment->id]),
            );
        }, 'payment_paid', $order->id);
    }

    public function notifyCardToCardRejected(Order $order, ?string $note = null): void
    {
        $this->safely(function () use ($order, $note): void {
            $context = array_merge($this->orderContext($order), [
                'note' => $this->truncateNote($note),
            ]);

            $this->sms->send(
                $order->customer_mobile ?? '',
                $this->templates->render(SmsMessageType::CardToCardRejected, $context),
                SmsMessageType::CardToCardRejected,
                $this->orderMeta($order),
            );
        }, 'card_to_card_rejected', $order->id);
    }

    public function notifyInstallmentRejected(Order $order, ?string $note = null): void
    {
        $this->safely(function () use ($order, $note): void {
            $context = array_merge($this->orderContext($order), [
                'note' => $this->truncateNote($note),
            ]);

            $this->sms->send(
                $order->customer_mobile ?? '',
                $this->templates->render(SmsMessageType::InstallmentRejected, $context),
                SmsMessageType::InstallmentRejected,
                $this->orderMeta($order),
            );
        }, 'installment_rejected', $order->id);
    }

    public function notifyLicenseActivated(SpotPlayerLicense $license): void
    {
        $this->safely(function () use ($license): void {
            $license->loadMissing(['order', 'coursePackage']);
            $order = $license->order;

            $context = [
                'package' => $license->coursePackage?->title ?? '',
                'order_number' => $order?->order_number ?? '',
            ];

            $this->sms->send(
                $order?->customer_mobile ?? '',
                $this->templates->render(SmsMessageType::LicenseActivated, $context),
                SmsMessageType::LicenseActivated,
                [
                    'license_id' => $license->id,
                    'order_id' => $order?->id,
                ],
            );
        }, 'license_activated', $license->id);
    }

    public function notifySupportTicketCreatedAdmin(SupportTicket $ticket): void
    {
        $this->safely(function () use ($ticket): void {
            $context = $this->ticketContext($ticket);

            $this->sms->sendToAdmin(
                $this->templates->render(SmsMessageType::SupportTicketCreatedAdmin, $context),
                SmsMessageType::SupportTicketCreatedAdmin,
                $this->ticketMeta($ticket),
            );
        }, 'support_ticket_created_admin', $ticket->id);
    }

    public function notifySupportTicketRepliedUser(SupportTicket $ticket): void
    {
        $this->safely(function () use ($ticket): void {
            $context = $this->ticketContext($ticket);

            $this->sms->send(
                $ticket->customer_mobile ?? '',
                $this->templates->render(SmsMessageType::SupportTicketRepliedUser, $context),
                SmsMessageType::SupportTicketRepliedUser,
                $this->ticketMeta($ticket),
            );
        }, 'support_ticket_replied_user', $ticket->id);
    }

    /**
     * @param  callable(): void  $callback
     */
    private function safely(callable $callback, string $event, int $referenceId): void
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            Log::warning('SMS notification failed.', [
                'event' => $event,
                'reference_id' => $referenceId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function installmentContext(Order $order): array
    {
        /** @var Payment|null $payment */
        $payment = $order->payments->first();

        return array_merge($this->orderContext($order), [
            'requested_term' => InstallmentTermLabels::fromPaymentMeta($payment?->meta) ?? '',
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function orderContext(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'amount' => TomanFormatter::format($order->final_amount_toman),
            'customer_name' => $order->customer_name,
            'customer_mobile' => $order->customer_mobile,
            'package' => $order->coursePackage?->title,
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function orderMeta(Order $order): array
    {
        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function ticketContext(SupportTicket $ticket): array
    {
        return [
            'subject' => $ticket->subject,
            'customer_name' => $ticket->customer_name,
            'customer_mobile' => $ticket->customer_mobile,
            'category' => SupportTicketStatusLabels::category($ticket->category),
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function ticketMeta(SupportTicket $ticket): array
    {
        return [
            'support_ticket_id' => $ticket->id,
            'user_id' => $ticket->user_id,
        ];
    }

    private function truncateNote(?string $note): string
    {
        if ($note === null || $note === '') {
            return '';
        }

        return mb_strlen($note) > 100 ? mb_substr($note, 0, 100).'…' : $note;
    }
}
