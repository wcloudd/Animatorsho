<?php

namespace App\Services;

use App\Enums\CoursePackageType;
use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Sms\SmsNotifier;
use App\Support\InstallmentPricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CheckoutOrderService
{
    public function __construct(
        private readonly AnimatorshoCatalogService $catalog,
        private readonly UserPackagePurchaseGuard $purchaseGuard,
        private readonly SiteSettingsService $siteSettings,
        private readonly SmsNotifier $smsNotifier,
    ) {}

    /**
     * @param  array{customer_name: string, customer_mobile: string}  $customerData
     * @param  array{installment_term: string, note: ?string}|null  $installmentData
     * @return array{order: Order, resultStatus: string}
     */
    public function create(
        User $user,
        string $package,
        string $payment,
        ?string $chapterSlug,
        array $customerData,
        ?array $installmentData = null,
        string $paymentChannel = 'online',
    ): array {
        $coursePackage = $this->resolvePackage($package, $payment, $chapterSlug);

        if (! $this->siteSettings->arePurchasesEnabled()) {
            throw ValidationException::withMessages([
                'package' => SiteSettingsService::PURCHASES_DISABLED_MESSAGE,
            ]);
        }

        if ($this->purchaseGuard->hasBlockingAccess($user, $coursePackage)) {
            throw ValidationException::withMessages([
                'package' => $this->purchaseGuard->message(),
            ]);
        }

        return match ($package) {
            'full' => match ($payment) {
                'cash' => $paymentChannel === 'card_to_card'
                    ? $this->createFullCardToCardOrder($user, $coursePackage, $customerData)
                    : $this->createFullCashOrder($user, $coursePackage, $customerData),
                'installment' => $paymentChannel === 'card_to_card'
                    ? $this->createFullInstallmentCardToCardOrder(
                        $user,
                        $coursePackage,
                        $customerData,
                        $installmentData ?? [],
                    )
                    : $this->createFullInstallmentOrder(
                        $user,
                        $coursePackage,
                        $customerData,
                        $installmentData ?? [],
                    ),
                default => throw new InvalidArgumentException('Invalid payment type.'),
            },
            'chapter' => $paymentChannel === 'card_to_card'
                ? $this->createChapterCardToCardOrder($user, $coursePackage, $customerData)
                : $this->createChapterCashOrder($user, $coursePackage, $customerData),
            default => throw new InvalidArgumentException('Invalid package type.'),
        };
    }

    public function resolvePackage(string $package, string $payment, ?string $chapterSlug): CoursePackage
    {
        if ($package === 'full') {
            if ($payment === 'installment' && $chapterSlug !== null) {
                throw new InvalidArgumentException('Installment is only available for the full course.');
            }

            $coursePackage = $this->catalog->findActivePackageBySlug(
                AnimatorshoCatalogService::FULL_PACKAGE_SLUG,
            );

            if (
                $coursePackage === null
                || $coursePackage->type !== CoursePackageType::FullCourse
            ) {
                throw new InvalidArgumentException('Full course package is not available.');
            }

            return $coursePackage;
        }

        if ($payment === 'installment') {
            throw new InvalidArgumentException('Installment is only available for the full course.');
        }

        if ($chapterSlug === null || $chapterSlug === '') {
            throw new InvalidArgumentException('Chapter slug is required.');
        }

        $coursePackage = $this->catalog->findActivePackageBySlug($chapterSlug);

        if (
            $coursePackage === null
            || $coursePackage->type !== CoursePackageType::Chapter
        ) {
            throw new InvalidArgumentException('Chapter package is not available.');
        }

        return $coursePackage;
    }

    /**
     * @param  array{customer_name: string, customer_mobile: string}  $customerData
     * @return array{order: Order, resultStatus: string}
     */
    private function createFullCashOrder(User $user, CoursePackage $coursePackage, array $customerData): array
    {
        return $this->persistOrder(
            user: $user,
            coursePackage: $coursePackage,
            orderStatus: OrderStatus::Pending,
            orderPaymentType: OrderPaymentType::Cash,
            paymentMethod: PaymentMethod::Zarinpal,
            paymentStatus: PaymentStatus::Pending,
            resultStatus: 'payment-pending',
            customerName: $customerData['customer_name'],
            customerMobile: $customerData['customer_mobile'],
        );
    }

    /**
     * @param  array{customer_name: string, customer_mobile: string}  $customerData
     * @param  array{installment_term?: string, note?: ?string}  $installmentData
     * @return array{order: Order, resultStatus: string}
     */
    private function createFullInstallmentOrder(
        User $user,
        CoursePackage $coursePackage,
        array $customerData,
        array $installmentData,
    ): array {
        ['pricing' => $pricing, 'meta' => $paymentMeta] = $this->buildInstallmentContext(
            $coursePackage,
            $installmentData,
        );

        return $this->persistOrder(
            user: $user,
            coursePackage: $coursePackage,
            orderStatus: OrderStatus::InstallmentDownPaymentPending,
            orderPaymentType: OrderPaymentType::Installment,
            paymentMethod: PaymentMethod::Installment,
            paymentStatus: PaymentStatus::Pending,
            resultStatus: 'installment-review',
            customerName: $customerData['customer_name'],
            customerMobile: $customerData['customer_mobile'],
            paymentMeta: $paymentMeta,
            orderAmountToman: $pricing['cash_price_toman'],
            orderFinalAmountToman: $pricing['installment_total_toman'],
            paymentAmountToman: $pricing['down_payment_toman'],
        );
    }

    /**
     * Card-to-card installment down payment. Mirrors the online installment
     * order but the down payment is settled via an uploaded receipt: the order
     * waits in InstallmentDownPaymentReview until an admin approves the receipt,
     * which then moves it into the same InstallmentReview state the online down
     * payment reaches. No SpotPlayer license is issued at this stage.
     *
     * @param  array{customer_name: string, customer_mobile: string}  $customerData
     * @param  array{installment_term?: string, note?: ?string}  $installmentData
     * @return array{order: Order, resultStatus: string}
     */
    private function createFullInstallmentCardToCardOrder(
        User $user,
        CoursePackage $coursePackage,
        array $customerData,
        array $installmentData,
    ): array {
        ['pricing' => $pricing, 'meta' => $paymentMeta] = $this->buildInstallmentContext(
            $coursePackage,
            $installmentData,
        );

        $paymentMeta = [
            ...$paymentMeta,
            'down_payment_channel' => 'card_to_card',
            'customer_name' => $customerData['customer_name'],
            'customer_mobile' => $customerData['customer_mobile'],
        ];

        return $this->persistOrder(
            user: $user,
            coursePackage: $coursePackage,
            orderStatus: OrderStatus::InstallmentDownPaymentReview,
            orderPaymentType: OrderPaymentType::Installment,
            paymentMethod: PaymentMethod::Installment,
            paymentStatus: PaymentStatus::Reviewing,
            resultStatus: 'manual-review',
            customerName: $customerData['customer_name'],
            customerMobile: $customerData['customer_mobile'],
            paymentMeta: $paymentMeta,
            orderAmountToman: $pricing['cash_price_toman'],
            orderFinalAmountToman: $pricing['installment_total_toman'],
            paymentAmountToman: $pricing['down_payment_toman'],
        );
    }

    /**
     * @param  array{installment_term?: string, note?: ?string}  $installmentData
     * @return array{pricing: array<string, int>, meta: array<string, mixed>}
     */
    private function buildInstallmentContext(CoursePackage $coursePackage, array $installmentData): array
    {
        $term = $installmentData['installment_term'] ?? null;

        if (! is_string($term) || $term === '') {
            throw new InvalidArgumentException('Installment term is required.');
        }

        $pricing = InstallmentPricing::calculate((int) $coursePackage->price_toman, $term);

        return [
            'pricing' => $pricing,
            'meta' => [
                'requested_term' => $term,
                'months' => $pricing['months'],
                'note' => $installmentData['note'] ?? null,
                'down_payment_percent' => $pricing['down_payment_percent'],
                'cash_price_toman' => $pricing['cash_price_toman'],
                'extra_amount_toman' => $pricing['extra_amount_toman'],
                'installment_total_toman' => $pricing['installment_total_toman'],
                'down_payment_toman' => $pricing['down_payment_toman'],
                'remaining_toman' => $pricing['remaining_toman'],
                'submitted_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * @param  array{customer_name: string, customer_mobile: string}  $customerData
     * @return array{order: Order, resultStatus: string}
     */
    private function createChapterCashOrder(User $user, CoursePackage $coursePackage, array $customerData): array
    {
        return $this->persistOrder(
            user: $user,
            coursePackage: $coursePackage,
            orderStatus: OrderStatus::Pending,
            orderPaymentType: OrderPaymentType::Cash,
            paymentMethod: PaymentMethod::Zarinpal,
            paymentStatus: PaymentStatus::Pending,
            resultStatus: 'payment-pending',
            customerName: $customerData['customer_name'],
            customerMobile: $customerData['customer_mobile'],
        );
    }

    /**
     * @param  array{customer_name: string, customer_mobile: string}  $customerData
     * @return array{order: Order, resultStatus: string}
     */
    private function createFullCardToCardOrder(User $user, CoursePackage $coursePackage, array $customerData): array
    {
        return $this->createCardToCardOrder($user, $coursePackage, $customerData);
    }

    /**
     * @param  array{customer_name: string, customer_mobile: string}  $customerData
     * @return array{order: Order, resultStatus: string}
     */
    private function createChapterCardToCardOrder(User $user, CoursePackage $coursePackage, array $customerData): array
    {
        return $this->createCardToCardOrder($user, $coursePackage, $customerData);
    }

    /**
     * @param  array{customer_name: string, customer_mobile: string}  $customerData
     * @return array{order: Order, resultStatus: string}
     */
    private function createCardToCardOrder(User $user, CoursePackage $coursePackage, array $customerData): array
    {
        $paymentMeta = [
            'customer_name' => $customerData['customer_name'],
            'customer_mobile' => $customerData['customer_mobile'],
            'submitted_at' => now()->toIso8601String(),
        ];

        return $this->persistOrder(
            user: $user,
            coursePackage: $coursePackage,
            orderStatus: OrderStatus::ManualReview,
            orderPaymentType: OrderPaymentType::CardToCard,
            paymentMethod: PaymentMethod::CardToCard,
            paymentStatus: PaymentStatus::Reviewing,
            resultStatus: 'manual-review',
            customerName: $customerData['customer_name'],
            customerMobile: $customerData['customer_mobile'],
            paymentMeta: $paymentMeta,
        );
    }

    /**
     * @return array{order: Order, resultStatus: string}
     */
    private function persistOrder(
        User $user,
        CoursePackage $coursePackage,
        OrderStatus $orderStatus,
        OrderPaymentType $orderPaymentType,
        PaymentMethod $paymentMethod,
        PaymentStatus $paymentStatus,
        string $resultStatus,
        string $customerName,
        string $customerMobile,
        ?array $paymentMeta = null,
        ?int $orderAmountToman = null,
        ?int $orderFinalAmountToman = null,
        ?int $paymentAmountToman = null,
    ): array {
        $order = DB::transaction(function () use (
            $user,
            $coursePackage,
            $orderStatus,
            $orderPaymentType,
            $paymentMethod,
            $paymentStatus,
            $customerName,
            $customerMobile,
            $paymentMeta,
            $orderAmountToman,
            $orderFinalAmountToman,
            $paymentAmountToman,
        ): Order {
            $order = new Order([
                'user_id' => $user->id,
                'course_package_id' => $coursePackage->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => $orderStatus,
                'payment_type' => $orderPaymentType,
                'customer_name' => $customerName,
                'customer_mobile' => $customerMobile,
            ]);

            $order->snapshotAmountsFromPackage($coursePackage);

            if ($orderAmountToman !== null) {
                $order->amount_toman = $orderAmountToman;
            }

            if ($orderFinalAmountToman !== null) {
                $order->final_amount_toman = $orderFinalAmountToman;
            }

            $order->save();

            Payment::query()->create([
                'order_id' => $order->id,
                'method' => $paymentMethod,
                'status' => $paymentStatus,
                'amount_toman' => $paymentAmountToman ?? $order->final_amount_toman,
                'meta' => $paymentMeta,
            ]);

            return $order;
        });

        if ($paymentMethod !== PaymentMethod::Installment) {
            $this->smsNotifier->notifyOrderCreated($order);
        }

        return [
            'order' => $order,
            'resultStatus' => $resultStatus,
        ];
    }
}
