<?php

namespace App\Http\Controllers;

use App\Enums\CoursePackageType;
use App\Services\AnimatorshoCatalogService;
use App\Services\CheckoutOrderService;
use App\Services\PaymentReceiptStorageService;
use App\Services\UserPackagePurchaseGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly AnimatorshoCatalogService $catalog,
        private readonly PaymentReceiptStorageService $receipts,
        private readonly CheckoutOrderService $checkoutOrders,
        private readonly UserPackagePurchaseGuard $purchaseGuard,
    ) {}

    public function index(): Response|RedirectResponse
    {
        $catalog = $this->catalog->catalogForInertia();

        if ($catalog === null) {
            return redirect()->route('home');
        }

        return Inertia::render('checkout/index', $catalog);
    }

    public function confirm(Request $request): Response|RedirectResponse
    {
        $catalog = $this->catalog->catalogForInertia();

        if ($catalog === null) {
            return redirect()->route('checkout');
        }

        $packageParam = $request->query('package');
        $paymentParam = $request->query('payment');
        $chapterSlug = $request->query('chapter');

        if ($packageParam === 'full') {
            $summary = $this->resolveFullPackageSummary($paymentParam, $catalog['fullPackage']);

            if ($summary === null) {
                return redirect()->route('checkout');
            }

            return Inertia::render('checkout/confirm', $this->confirmPageProps(
                summary: $summary,
                chapterPackages: $catalog['chapterPackages'],
                showInstallmentForm: $paymentParam === 'installment',
                orderContext: [
                    'package' => 'full',
                    'payment' => $paymentParam,
                    'chapter' => null,
                ],
                request: $request,
            ));
        }

        if ($packageParam === 'chapter') {
            if (is_string($chapterSlug) && $chapterSlug !== '') {
                $chapterPackage = $this->catalog->findActivePackageBySlug($chapterSlug);

                if (
                    $chapterPackage === null
                    || $chapterPackage->type !== CoursePackageType::Chapter
                ) {
                    return redirect()->route('checkout');
                }

                $mapped = $this->catalog->mapPackage($chapterPackage);

                return Inertia::render('checkout/confirm', $this->confirmPageProps(
                    summary: $this->chapterSummary($mapped, withPrice: true),
                    chapterPackages: $catalog['chapterPackages'],
                    showInstallmentForm: false,
                    orderContext: [
                        'package' => 'chapter',
                        'payment' => 'cash',
                        'chapter' => $chapterSlug,
                    ],
                    request: $request,
                ));
            }

            return Inertia::render('checkout/confirm', $this->confirmPageProps(
                summary: $this->chapterSummary($catalog['chapterPackages'][0] ?? null, withPrice: false),
                chapterPackages: $catalog['chapterPackages'],
                showInstallmentForm: false,
                orderContext: null,
                request: $request,
            ));
        }

        return redirect()->route('checkout');
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  list<array<string, mixed>>  $chapterPackages
     * @param  array{package: string, payment: string, chapter: string|null}|null  $orderContext
     * @return array<string, mixed>
     */
    private function confirmPageProps(
        array $summary,
        array $chapterPackages,
        bool $showInstallmentForm,
        ?array $orderContext,
        Request $request,
    ): array {
        $user = $request->user();
        $duplicatePurchaseBlocked = false;
        $duplicatePurchaseMessage = null;

        if ($user !== null && $orderContext !== null) {
            try {
                $coursePackage = $this->checkoutOrders->resolvePackage(
                    $orderContext['package'],
                    $orderContext['payment'],
                    $orderContext['chapter'],
                );

                if ($this->purchaseGuard->hasBlockingAccess($user, $coursePackage)) {
                    $duplicatePurchaseBlocked = true;
                    $duplicatePurchaseMessage = $this->purchaseGuard->message();
                }
            } catch (\InvalidArgumentException) {
                // Invalid confirm context is handled elsewhere; do not block here.
            }
        }

        return [
            'summary' => $summary,
            'showChapterSelector' => $orderContext === null,
            'chapterPackages' => $chapterPackages,
            'showInstallmentForm' => $showInstallmentForm,
            'orderContext' => $orderContext,
            'customerDefaults' => $user !== null
                ? ['name' => $user->name]
                : null,
            'duplicatePurchaseBlocked' => $duplicatePurchaseBlocked,
            'duplicatePurchaseMessage' => $duplicatePurchaseMessage,
            'cardToCardAvailable' => $this->receipts->isConfigured(),
            'cardToCardTransfer' => $this->receipts->isConfigured()
                ? [
                    'cardNumber' => (string) config('card_to_card.card_number'),
                    'cardOwnerName' => (string) config('card_to_card.card_owner_name'),
                ]
                : null,
            'cardToCardUnavailableMessage' => $this->receipts->isConfigured()
                ? null
                : 'اطلاعات کارت‌به‌کارت هنوز توسط مدیر سایت تنظیم نشده است.',
        ];
    }

    /**
     * @param  array{slug: string, title: string, priceToman: int, chapterNumber: int|null}  $fullPackage
     * @return array<string, mixed>|null
     */
    private function resolveFullPackageSummary(?string $paymentParam, array $fullPackage): ?array
    {
        if ($paymentParam === 'cash') {
            return [
                'variant' => 'full-cash',
                'title' => $fullPackage['title'],
                'paymentType' => 'پرداخت نقدی',
                'priceLine' => $this->catalog->formatPrice($fullPackage['priceToman']),
                'mainLine' => null,
                'description' => 'دسترسی به فصل‌های اصلی، ورکشاپ‌های تکمیلی، آپدیت‌های دوره و لایسنس SpotPlayer.',
                'primaryCtaLabel' => 'انتخاب روش پرداخت',
                'primaryCtaHref' => '#payment-methods',
            ];
        }

        if ($paymentParam === 'installment') {
            return [
                'variant' => 'full-installment',
                'title' => $fullPackage['title'],
                'paymentType' => 'خرید اقساطی',
                'priceLine' => null,
                'mainLine' => '۴۰٪ پیش‌پرداخت / مابقی اقساط',
                'description' => 'درخواست شما توسط پشتیبانی بررسی می‌شود و بعد از تأیید، مسیر پرداخت مرحله‌ای برایتان فعال می‌شود.',
                'primaryCtaLabel' => 'تکمیل درخواست اقساطی',
                'primaryCtaHref' => '#installment-form',
            ];
        }

        return null;
    }

    /**
     * @param  array{slug: string, title: string, priceToman: int, chapterNumber: int|null}|null  $chapterPackage
     * @return array<string, mixed>
     */
    private function chapterSummary(?array $chapterPackage, bool $withPrice): array
    {
        return [
            'variant' => 'chapter',
            'title' => $withPrice && $chapterPackage !== null
                ? $chapterPackage['title']
                : 'خرید فصل جداگانه',
            'paymentType' => 'پرداخت نقدی فصل انتخابی',
            'priceLine' => $withPrice && $chapterPackage !== null
                ? $this->catalog->formatPrice($chapterPackage['priceToman'])
                : null,
            'mainLine' => null,
            'description' => 'یکی از فصل‌های دوره را انتخاب کن و فقط همان بخش را شروع کن.',
            'primaryCtaLabel' => 'انتخاب روش پرداخت',
            'primaryCtaHref' => '#payment-methods',
        ];
    }
}
