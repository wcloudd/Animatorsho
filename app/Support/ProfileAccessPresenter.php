<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Services\OnlinePaymentRecoveryService;
use Illuminate\Support\Collection;

class ProfileAccessPresenter
{
    public function __construct(
        private readonly OnlinePaymentRecoveryService $onlinePaymentRecovery,
    ) {}

    private const int PRIORITY_ACTIVE_LICENSE = 1;

    private const int PRIORITY_INSTALLMENT_REJECTED_WITH_DOWN_PAYMENT = 2;

    private const int PRIORITY_REVOKED_LICENSE = 3;

    private const int PRIORITY_PAID_LICENSE_PENDING = 4;

    private const int PRIORITY_PAYMENT_REVIEWING = 5;

    private const int PRIORITY_PAYMENT_PENDING = 6;

    private const int PRIORITY_FAILED_OR_CANCELLED = 7;

    /**
     * @param  Collection<int, Order>  $orders
     * @param  Collection<int, SpotPlayerLicense>  $licenses
     * @return list<array{
     *     id: string,
     *     packageId: int,
     *     orderId: ?int,
     *     licenseId: ?int,
     *     title: string,
     *     accessState: string,
     *     statusLabel: string,
     *     statusTone: string,
     *     description: string,
     *     paymentMethod: ?string,
     *     amountToman: ?int,
     *     licenseKey: ?string,
     *     rejectionReason: ?string,
     *     nextAction: ?array{label: string, href: string, external: bool},
     *     primaryAction: ?array{label: string, href: string, method: string},
     *     secondaryAction: ?array{label: string, href: string, method: string, requiresConfirm: bool}
     * }>
     */
    public function present(Collection $orders, Collection $licenses): array
    {
        $packageIds = $orders
            ->pluck('course_package_id')
            ->merge($licenses->pluck('course_package_id'))
            ->unique()
            ->filter()
            ->values();

        $accessItems = [];

        foreach ($packageIds as $packageId) {
            /** @var int $packageId */
            $packageOrders = $orders->where('course_package_id', $packageId);
            $packageLicenses = $licenses->where('course_package_id', $packageId);

            $candidates = [];

            foreach ($packageOrders as $order) {
                $candidate = $this->candidateFromOrder($order);

                if ($candidate !== null) {
                    $candidates[] = $candidate;
                }
            }

            foreach ($packageLicenses as $license) {
                if ($license->order_id !== null && $packageOrders->contains('id', $license->order_id)) {
                    continue;
                }

                $candidate = $this->candidateFromLicenseOnly($license);

                if ($candidate !== null) {
                    $candidates[] = $candidate;
                }
            }

            if ($candidates === []) {
                continue;
            }

            usort(
                $candidates,
                fn (array $left, array $right): int => $left['priority'] <=> $right['priority']
                    ?: ($right['sortTimestamp'] <=> $left['sortTimestamp']),
            );

            $accessItems[] = $this->buildAccessItem($packageId, $candidates[0]);
        }

        usort(
            $accessItems,
            fn (array $left, array $right): int => strcmp($left['title'], $right['title']),
        );

        return array_values($accessItems);
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @param  Collection<int, SpotPlayerLicense>  $licenses
     */
    public function hasBlockingPackageAccess(
        Collection $orders,
        Collection $licenses,
        int $packageId,
    ): bool {
        $packageOrders = $orders->where('course_package_id', $packageId);
        $packageLicenses = $licenses->where('course_package_id', $packageId);

        foreach ($packageOrders as $order) {
            $candidate = $this->candidateFromOrder($order);

            if ($candidate !== null && $this->isBlockingAccessState($candidate['accessState'])) {
                return true;
            }
        }

        foreach ($packageLicenses as $license) {
            if ($license->order_id !== null && $packageOrders->contains('id', $license->order_id)) {
                continue;
            }

            $candidate = $this->candidateFromLicenseOnly($license);

            if ($candidate !== null && $this->isBlockingAccessState($candidate['accessState'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @param  Collection<int, SpotPlayerLicense>  $licenses
     */
    public function accessStateForPackage(
        Collection $orders,
        Collection $licenses,
        int $packageId,
    ): ?string {
        $packageOrders = $orders->where('course_package_id', $packageId);
        $packageLicenses = $licenses->where('course_package_id', $packageId);

        $candidates = [];

        foreach ($packageOrders as $order) {
            $candidate = $this->candidateFromOrder($order);

            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        foreach ($packageLicenses as $license) {
            if ($license->order_id !== null && $packageOrders->contains('id', $license->order_id)) {
                continue;
            }

            $candidate = $this->candidateFromLicenseOnly($license);

            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            fn (array $left, array $right): int => $left['priority'] <=> $right['priority']
                ?: ($right['sortTimestamp'] <=> $left['sortTimestamp']),
        );

        return $candidates[0]['accessState'];
    }

    private function isBlockingAccessState(string $accessState): bool
    {
        return in_array($accessState, [
            'access_active',
            'paid_license_pending',
            'installment_reviewing',
            'payment_reviewing',
            'payment_pending',
        ], true);
    }

    /**
     * @return ?array{
     *     priority: int,
     *     accessState: string,
     *     sortTimestamp: int,
     *     orderId: ?int,
     *     licenseId: ?int,
     *     title: string,
     *     paymentMethod: ?string,
     *     amountToman: ?int,
     *     licenseKey: ?string,
     *     rejectionReason: ?string,
     *     recoverableOnline: bool
     * }
     */
    private function candidateFromOrder(Order $order): ?array
    {
        /** @var Payment|null $payment */
        $payment = $order->payments->first();
        $license = $order->spotPlayerLicense;
        $title = $order->coursePackage?->title ?? 'بسته دوره';
        $sortTimestamp = $order->created_at?->getTimestamp() ?? 0;

        if ($license?->status === SpotPlayerLicenseStatus::Active) {
            return $this->candidate(
                self::PRIORITY_ACTIVE_LICENSE,
                'access_active',
                $sortTimestamp,
                $order->id,
                $license->id,
                $title,
                $this->paymentMethodLabel($order, $payment),
                $order->final_amount_toman,
                $license->license_key,
            );
        }

        if ($license?->status === SpotPlayerLicenseStatus::Revoked) {
            return $this->candidate(
                self::PRIORITY_REVOKED_LICENSE,
                'license_revoked',
                $sortTimestamp,
                $order->id,
                $license->id,
                $title,
                $this->paymentMethodLabel($order, $payment),
                $order->final_amount_toman,
                null,
            );
        }

        if ($order->status === OrderStatus::Paid && ($license === null || $license->status === SpotPlayerLicenseStatus::Pending)) {
            return $this->candidate(
                self::PRIORITY_PAID_LICENSE_PENDING,
                'paid_license_pending',
                $sortTimestamp,
                $order->id,
                $license?->id,
                $title,
                $this->paymentMethodLabel($order, $payment),
                $order->final_amount_toman,
                null,
            );
        }

        if ($order->status === OrderStatus::InstallmentDownPaymentPending) {
            return $this->candidate(
                self::PRIORITY_PAYMENT_PENDING,
                'installment_down_payment_pending',
                $sortTimestamp,
                $order->id,
                $license?->id,
                $title,
                $this->paymentMethodLabel($order, $payment),
                $order->final_amount_toman,
                null,
            );
        }

        if ($order->status === OrderStatus::InstallmentReview) {
            return $this->candidate(
                self::PRIORITY_PAYMENT_REVIEWING,
                'installment_reviewing',
                $sortTimestamp,
                $order->id,
                $license?->id,
                $title,
                $this->paymentMethodLabel($order, $payment),
                $order->final_amount_toman,
                null,
            );
        }

        if ($order->status === OrderStatus::InstallmentDownPaymentReview) {
            // Card-to-card down payment receipt awaiting admin review. Treated as
            // an installment request under review (blocking, same as online).
            return $this->candidate(
                self::PRIORITY_PAYMENT_REVIEWING,
                'installment_reviewing',
                $sortTimestamp,
                $order->id,
                $license?->id,
                $title,
                $this->paymentMethodLabel($order, $payment),
                $order->final_amount_toman,
                null,
            );
        }

        if ($order->status === OrderStatus::InstallmentRejected) {
            $hasCapturedDownPayment = $this->hasCapturedInstallmentDownPayment($payment);

            return $this->candidate(
                $hasCapturedDownPayment
                    ? self::PRIORITY_INSTALLMENT_REJECTED_WITH_DOWN_PAYMENT
                    : self::PRIORITY_FAILED_OR_CANCELLED,
                'installment_rejected',
                $sortTimestamp,
                $order->id,
                $license?->id,
                $title,
                $this->paymentMethodLabel($order, $payment),
                $order->final_amount_toman,
                null,
                $this->rejectionNoteFromMeta($payment?->meta),
            );
        }

        if (
            $order->status === OrderStatus::ManualReview
            || $payment?->status === PaymentStatus::Reviewing
        ) {
            return $this->candidate(
                self::PRIORITY_PAYMENT_REVIEWING,
                'payment_reviewing',
                $sortTimestamp,
                $order->id,
                $license?->id,
                $title,
                $this->paymentMethodLabel($order, $payment),
                $order->final_amount_toman,
                null,
            );
        }

        if ($order->status === OrderStatus::Pending && $payment?->status === PaymentStatus::Pending) {
            return $this->candidateFromOrderWithRecovery($order, $this->candidate(
                self::PRIORITY_PAYMENT_PENDING,
                'payment_pending',
                $sortTimestamp,
                $order->id,
                $license?->id,
                $title,
                $this->paymentMethodLabel($order, $payment),
                $order->final_amount_toman,
                null,
            ));
        }

        if (
            $order->status === OrderStatus::Failed
            || $order->status === OrderStatus::Cancelled
            || $payment?->status === PaymentStatus::Failed
        ) {
            $accessState = $order->status === OrderStatus::Cancelled ? 'cancelled' : 'payment_failed';

            return $this->candidateFromOrderWithRecovery($order, $this->candidate(
                self::PRIORITY_FAILED_OR_CANCELLED,
                $accessState,
                $sortTimestamp,
                $order->id,
                $license?->id,
                $title,
                $this->paymentMethodLabel($order, $payment),
                $order->final_amount_toman,
                null,
                $accessState === 'payment_failed'
                    ? $this->rejectionReasonFromOrder($order)
                    : null,
            ));
        }

        return null;
    }

    /**
     * @return ?array{
     *     priority: int,
     *     accessState: string,
     *     sortTimestamp: int,
     *     orderId: ?int,
     *     licenseId: ?int,
     *     title: string,
     *     paymentMethod: ?string,
     *     amountToman: ?int,
     *     licenseKey: ?string,
     *     rejectionReason: ?string,
     *     recoverableOnline: bool
     * }
     */
    private function candidateFromLicenseOnly(SpotPlayerLicense $license): ?array
    {
        $title = $license->coursePackage?->title ?? 'بسته دوره';
        $sortTimestamp = $license->created_at?->getTimestamp() ?? 0;

        return match ($license->status) {
            SpotPlayerLicenseStatus::Active => $this->candidate(
                self::PRIORITY_ACTIVE_LICENSE,
                'access_active',
                $sortTimestamp,
                null,
                $license->id,
                $title,
                null,
                null,
                $license->license_key,
            ),
            SpotPlayerLicenseStatus::Revoked => $this->candidate(
                self::PRIORITY_REVOKED_LICENSE,
                'license_revoked',
                $sortTimestamp,
                null,
                $license->id,
                $title,
                null,
                null,
                null,
            ),
            SpotPlayerLicenseStatus::Pending => $this->candidate(
                self::PRIORITY_PAID_LICENSE_PENDING,
                'paid_license_pending',
                $sortTimestamp,
                null,
                $license->id,
                $title,
                null,
                null,
                null,
            ),
            SpotPlayerLicenseStatus::Failed => $this->candidate(
                self::PRIORITY_FAILED_OR_CANCELLED,
                'payment_failed',
                $sortTimestamp,
                null,
                $license->id,
                $title,
                null,
                null,
                null,
            ),
        };
    }

    /**
     * @param  array{
     *     priority: int,
     *     accessState: string,
     *     sortTimestamp: int,
     *     orderId: ?int,
     *     licenseId: ?int,
     *     title: string,
     *     paymentMethod: ?string,
     *     amountToman: ?int,
     *     licenseKey: ?string,
     *     rejectionReason: ?string,
     *     recoverableOnline: bool
     * }  $candidate
     * @return array{
     *     id: string,
     *     packageId: int,
     *     orderId: ?int,
     *     licenseId: ?int,
     *     title: string,
     *     accessState: string,
     *     statusLabel: string,
     *     statusTone: string,
     *     description: string,
     *     paymentMethod: ?string,
     *     amountToman: ?int,
     *     licenseKey: ?string,
     *     nextAction: ?array{label: string, href: string, external: bool},
     *     primaryAction: ?array{label: string, href: string, method: string},
     *     secondaryAction: ?array{label: string, href: string, method: string, requiresConfirm: bool}
     * }
     */
    private function buildAccessItem(int $packageId, array $candidate): array
    {
        $copy = $this->copyForState(
            $candidate['accessState'],
            $candidate['recoverableOnline'] ?? false,
        );

        $recoveryActions = $this->recoveryActionsForCandidate($candidate);

        return [
            'id' => 'package-'.$packageId,
            'packageId' => $packageId,
            'orderId' => $candidate['orderId'],
            'licenseId' => $candidate['licenseId'],
            'title' => $candidate['title'],
            'accessState' => $candidate['accessState'],
            'statusLabel' => $copy['statusLabel'],
            'statusTone' => $copy['statusTone'],
            'description' => $copy['description'],
            'paymentMethod' => $candidate['paymentMethod'],
            'amountToman' => $candidate['amountToman'],
            'licenseKey' => $candidate['accessState'] === 'access_active'
                ? $candidate['licenseKey']
                : null,
            'rejectionReason' => in_array($candidate['accessState'], ['payment_failed', 'installment_rejected'], true)
                ? $candidate['rejectionReason']
                : null,
            'nextAction' => $this->nextActionForState($candidate['accessState']),
            'primaryAction' => $recoveryActions['primaryAction'],
            'secondaryAction' => $recoveryActions['secondaryAction'],
        ];
    }

    /**
     * @param  array{
     *     orderId: ?int,
     *     recoverableOnline: bool
     * }  $candidate
     * @return array{
     *     primaryAction: ?array{label: string, href: string, method: string},
     *     secondaryAction: ?array{label: string, href: string, method: string, requiresConfirm: bool}
     * }
     */
    private function recoveryActionsForCandidate(array $candidate): array
    {
        if (! ($candidate['recoverableOnline'] ?? false) || $candidate['orderId'] === null) {
            return [
                'primaryAction' => null,
                'secondaryAction' => null,
            ];
        }

        return [
            'primaryAction' => [
                'label' => 'ادامه پرداخت',
                'href' => route('profile.orders.retry-online-payment', $candidate['orderId']),
                'method' => 'post',
            ],
            'secondaryAction' => [
                'label' => 'لغو سفارش',
                'href' => route('profile.orders.cancel', $candidate['orderId']),
                'method' => 'post',
                'requiresConfirm' => true,
            ],
        ];
    }

    /**
     * @param  array{
     *     priority: int,
     *     accessState: string,
     *     sortTimestamp: int,
     *     orderId: ?int,
     *     licenseId: ?int,
     *     title: string,
     *     paymentMethod: ?string,
     *     amountToman: ?int,
     *     licenseKey: ?string,
     *     rejectionReason: ?string
     * }  $candidate
     * @return array{
     *     priority: int,
     *     accessState: string,
     *     sortTimestamp: int,
     *     orderId: ?int,
     *     licenseId: ?int,
     *     title: string,
     *     paymentMethod: ?string,
     *     amountToman: ?int,
     *     licenseKey: ?string,
     *     rejectionReason: ?string,
     *     recoverableOnline: bool
     * }
     */
    private function candidateFromOrderWithRecovery(Order $order, array $candidate): array
    {
        $candidate['recoverableOnline'] = $this->onlinePaymentRecovery->isRecoverableOnlineOrder($order);

        return $candidate;
    }

    /**
     * @return array{statusLabel: string, statusTone: string, description: string}
     */
    private function copyForState(string $accessState, bool $recoverableOnline = false): array
    {
        return match ($accessState) {
            'access_active' => [
                'statusLabel' => 'دسترسی فعال',
                'statusTone' => 'success',
                'description' => 'لایسنس SpotPlayer فعال است و می‌توانید از طریق SpotPlayer به دوره دسترسی داشته باشید.',
            ],
            'license_revoked' => [
                'statusLabel' => 'لایسنس غیرفعال شده',
                'statusTone' => 'neutral',
                'description' => 'برای پیگیری با پشتیبانی در ارتباط باشید.',
            ],
            'paid_license_pending' => [
                'statusLabel' => 'پرداخت تأیید شده، لایسنس در انتظار فعال‌سازی',
                'statusTone' => 'warning',
                'description' => 'لایسنس SpotPlayer بعد از فعال‌سازی توسط پشتیبانی همین‌جا نمایش داده می‌شود.',
            ],
            'installment_down_payment_pending' => [
                'statusLabel' => 'در انتظار پرداخت پیش‌پرداخت',
                'statusTone' => 'warning',
                'description' => 'برای ثبت درخواست اقساطی، پرداخت پیش‌پرداخت ۴۰٪ را از طریق درگاه تکمیل کنید.',
            ],
            'installment_reviewing' => [
                'statusLabel' => 'در انتظار بررسی خرید اقساطی',
                'statusTone' => 'warning',
                'description' => 'پیش‌پرداخت ۴۰٪ شما با موفقیت ثبت شد و درخواست اقساطی شما در حال بررسی توسط پشتیبانی است.',
            ],
            'installment_rejected' => [
                'statusLabel' => 'درخواست اقساطی رد شد — پیش‌پرداخت ثبت شده',
                'statusTone' => 'warning',
                'description' => 'درخواست خرید اقساطی شما رد شد، اما پیش‌پرداخت شما ثبت شده است. وضعیت پیگیری مالی به‌صورت دستی توسط پشتیبانی بررسی می‌شود.',
            ],
            'payment_reviewing' => [
                'statusLabel' => 'در انتظار بررسی پرداخت',
                'statusTone' => 'warning',
                'description' => 'رسید شما ثبت شده و پشتیبانی در حال بررسی آن است.',
            ],
            'payment_pending' => [
                'statusLabel' => 'در انتظار پرداخت',
                'statusTone' => 'warning',
                'description' => $recoverableOnline
                    ? 'برای تکمیل خرید، پرداخت را ادامه دهید یا در صورت نیاز سفارش را لغو کنید.'
                    : 'برای تکمیل خرید، پرداخت را انجام دهید یا وضعیت سفارش را پیگیری کنید.',
            ],
            'payment_failed' => [
                'statusLabel' => 'پرداخت ناموفق',
                'statusTone' => 'neutral',
                'description' => $recoverableOnline
                    ? 'پرداخت تکمیل نشد. می‌توانید دوباره تلاش کنید یا سفارش را لغو کنید.'
                    : 'در صورت نیاز دوباره ثبت‌نام کنید یا با پشتیبانی در ارتباط باشید.',
            ],
            'cancelled' => [
                'statusLabel' => 'لغو شده',
                'statusTone' => 'neutral',
                'description' => 'این سفارش لغو شده است. در صورت نیاز می‌توانید دوباره ثبت‌نام کنید.',
            ],
            default => [
                'statusLabel' => 'وضعیت نامشخص',
                'statusTone' => 'neutral',
                'description' => 'برای پیگیری با پشتیبانی در ارتباط باشید.',
            ],
        };
    }

    /**
     * @return ?array{label: string, href: string, external: bool}
     */
    private function nextActionForState(string $accessState): ?array
    {
        return match ($accessState) {
            'payment_failed', 'cancelled' => [
                'label' => 'مشاهده دوره‌ها',
                'href' => route('checkout'),
                'external' => false,
            ],
            'license_revoked', 'payment_reviewing', 'installment_reviewing', 'installment_rejected' => [
                'label' => 'ارتباط با پشتیبانی',
                'href' => route('support.index'),
                'external' => false,
            ],
            'installment_down_payment_pending' => [
                'label' => 'تکمیل پیش‌پرداخت',
                'href' => route('checkout'),
                'external' => false,
            ],
            default => null,
        };
    }

    private function paymentMethodLabel(Order $order, ?Payment $payment): ?string
    {
        if ($payment !== null) {
            return ProfileStatusLabels::paymentMethod($payment->method);
        }

        return ProfileStatusLabels::paymentType($order->payment_type);
    }

    /**
     * @return array{
     *     priority: int,
     *     accessState: string,
     *     sortTimestamp: int,
     *     orderId: ?int,
     *     licenseId: ?int,
     *     title: string,
     *     paymentMethod: ?string,
     *     amountToman: ?int,
     *     licenseKey: ?string,
     *     licenseKey: ?string,
     *     rejectionReason: ?string,
     *     recoverableOnline: bool
     * }
     */
    private function candidate(
        int $priority,
        string $accessState,
        int $sortTimestamp,
        ?int $orderId,
        ?int $licenseId,
        string $title,
        ?string $paymentMethod,
        ?int $amountToman,
        ?string $licenseKey,
        ?string $rejectionReason = null,
    ): array {
        return [
            'priority' => $priority,
            'accessState' => $accessState,
            'sortTimestamp' => $sortTimestamp,
            'orderId' => $orderId,
            'licenseId' => $licenseId,
            'title' => $title,
            'paymentMethod' => $paymentMethod,
            'amountToman' => $amountToman,
            'licenseKey' => $licenseKey,
            'rejectionReason' => $rejectionReason,
            'recoverableOnline' => false,
        ];
    }

    private function rejectionReasonFromOrder(Order $order): ?string
    {
        foreach ($order->payments as $payment) {
            if ($payment->status !== PaymentStatus::Failed) {
                continue;
            }

            $reason = $this->rejectionNoteFromMeta($payment->meta);

            if ($reason !== null) {
                return $reason;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function rejectionNoteFromMeta(?array $meta): ?string
    {
        if ($meta === null) {
            return null;
        }

        $note = $meta['rejection_note'] ?? null;

        if (! is_string($note)) {
            return null;
        }

        $note = trim($note);

        return $note === '' ? null : $note;
    }

    private function hasCapturedInstallmentDownPayment(?Payment $payment): bool
    {
        if ($payment === null || $payment->method !== PaymentMethod::Installment) {
            return false;
        }

        $meta = $payment->meta ?? [];

        if (($meta['down_payment_paid_at'] ?? null) !== null) {
            return true;
        }

        return $payment->status === PaymentStatus::Paid;
    }
}
