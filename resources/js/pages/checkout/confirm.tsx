import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AccountNoticeCard } from '@/components/checkout/account-notice-card';
import { CheckoutOrderForm } from '@/components/checkout/checkout-order-form';
import { ConfirmPageHeader } from '@/components/checkout/confirm-page-header';
import { CustomerInfoFields } from '@/components/checkout/customer-info-fields';
import { DuplicatePurchaseNotice } from '@/components/checkout/duplicate-purchase-notice';
import { InstallmentFormFields } from '@/components/checkout/installment-form-preview';
import { OrderSummaryCard } from '@/components/checkout/order-summary-card';
import { OrderSummaryFallbackCard } from '@/components/checkout/order-summary-fallback-card';
import { TrustNote } from '@/components/checkout/trust-note';
import { PageContainer } from '@/components/page-container';
import type { CatalogPackage } from '@/lib/checkout-catalog';
import type {
    CardToCardTransferDetails,
    CheckoutPaymentMethodId,
    OrderSummaryContent,
} from '@/lib/checkout-confirm';
import type { CheckoutOrderContext } from '@/lib/checkout-order';

type CheckoutConfirmProps = {
    summary: OrderSummaryContent | null;
    showChapterSelector: boolean;
    chapterPackages: CatalogPackage[];
    showInstallmentForm: boolean;
    orderContext: CheckoutOrderContext | null;
    customerDefaults: { name: string } | null;
    duplicatePurchaseBlocked: boolean;
    duplicatePurchaseMessage: string | null;
    cardToCardAvailable: boolean;
    cardToCardTransfer: CardToCardTransferDetails | null;
    cardToCardUnavailableMessage: string | null;
};

export default function CheckoutConfirm({
    summary,
    showChapterSelector,
    chapterPackages,
    showInstallmentForm,
    orderContext,
    customerDefaults,
    duplicatePurchaseBlocked,
    duplicatePurchaseMessage,
    cardToCardAvailable,
    cardToCardTransfer,
    cardToCardUnavailableMessage,
}: CheckoutConfirmProps) {
    const { auth } = usePage().props;
    const isAuthenticated = auth.user !== null;
    const isCashCheckout = orderContext?.payment === 'cash';
    const canSubmitOrder =
        isAuthenticated &&
        orderContext !== null &&
        !showChapterSelector &&
        !duplicatePurchaseBlocked;
    const [selectedPaymentMethod, setSelectedPaymentMethod] =
        useState<CheckoutPaymentMethodId>('online');

    const blockedMessage =
        duplicatePurchaseMessage ??
        'شما قبلاً برای این دوره ثبت‌نام یا درخواست فعال دارید. وضعیت آن را از پروفایل پیگیری کنید.';

    return (
        <>
            <Head title="تکمیل ثبت‌نام" />
            <PageContainer>
                <div className="flex flex-col gap-6">
                    <ConfirmPageHeader />

                    {summary ? (
                        duplicatePurchaseBlocked ? (
                            <>
                                <OrderSummaryCard
                                    summary={summary}
                                    showChapterSelector={showChapterSelector}
                                    chapterPackages={chapterPackages}
                                    cardToCardAvailable={cardToCardAvailable}
                                    cardToCardUnavailableMessage={
                                        cardToCardUnavailableMessage
                                    }
                                />
                                <DuplicatePurchaseNotice
                                    message={blockedMessage}
                                />
                            </>
                        ) : canSubmitOrder && orderContext ? (
                            <CheckoutOrderForm
                                context={orderContext}
                                customerDefaults={customerDefaults}
                                className="flex flex-col gap-6"
                                selectedPaymentMethod={selectedPaymentMethod}
                                onSelectPaymentMethod={setSelectedPaymentMethod}
                            >
                                {({
                                    processing,
                                    data,
                                    setData,
                                    errors,
                                    selectedPaymentMethod:
                                        formSelectedPaymentMethod,
                                    onSelectPaymentMethod,
                                }) => (
                                    <>
                                        <CustomerInfoFields
                                            data={data}
                                            setData={setData}
                                            errors={errors}
                                        />

                                        {errors.package ? (
                                            <DuplicatePurchaseNotice
                                                message={errors.package}
                                            />
                                        ) : null}

                                        <OrderSummaryCard
                                            summary={summary}
                                            showChapterSelector={
                                                showChapterSelector
                                            }
                                            chapterPackages={chapterPackages}
                                            orderContext={
                                                isCashCheckout
                                                    ? orderContext
                                                    : null
                                            }
                                            selectedPaymentMethod={
                                                isCashCheckout
                                                    ? formSelectedPaymentMethod
                                                    : undefined
                                            }
                                            onSelectPaymentMethod={
                                                isCashCheckout
                                                    ? onSelectPaymentMethod
                                                    : undefined
                                            }
                                            paymentProcessing={processing}
                                            cardToCardAvailable={
                                                cardToCardAvailable
                                            }
                                            cardToCardUnavailableMessage={
                                                cardToCardUnavailableMessage
                                            }
                                            cardToCardTransfer={
                                                cardToCardTransfer
                                            }
                                            receiptFile={data.receipt_image}
                                            onReceiptChange={(file) =>
                                                setData('receipt_image', file)
                                            }
                                            receiptError={errors.receipt_image}
                                        />

                                        {showInstallmentForm ? (
                                            <InstallmentFormFields
                                                processing={processing}
                                                data={data}
                                                setData={setData}
                                                errors={errors}
                                            />
                                        ) : null}
                                    </>
                                )}
                            </CheckoutOrderForm>
                        ) : (
                            <OrderSummaryCard
                                summary={summary}
                                showChapterSelector={showChapterSelector}
                                chapterPackages={chapterPackages}
                                cardToCardAvailable={cardToCardAvailable}
                                cardToCardUnavailableMessage={
                                    cardToCardUnavailableMessage
                                }
                            />
                        )
                    ) : (
                        <OrderSummaryFallbackCard />
                    )}

                    {!isAuthenticated ? <AccountNoticeCard /> : null}

                    <TrustNote />
                </div>
            </PageContainer>
        </>
    );
}
