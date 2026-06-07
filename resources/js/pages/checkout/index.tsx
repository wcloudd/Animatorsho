import { Head } from '@inertiajs/react';
import { PurchaseSection } from '@/components/landing/purchase-section';
import { PurchasesDisabledNotice } from '@/components/checkout/purchases-disabled-notice';
import type { CheckoutCatalogProps } from '@/lib/checkout-catalog';

type CheckoutIndexProps = CheckoutCatalogProps & {
    purchasesDisabled: boolean;
    purchasesDisabledMessage: string;
};

export default function CheckoutIndex({
    fullPackage,
    chapterPackages,
    purchasesDisabled,
    purchasesDisabledMessage,
}: CheckoutIndexProps) {
    return (
        <>
            <Head title="ثبت‌نام دوره" />
            <div className="pb-32">
                {purchasesDisabled ? (
                    <div className="px-4 pt-6">
                        <PurchasesDisabledNotice
                            message={purchasesDisabledMessage}
                        />
                    </div>
                ) : null}
                <PurchaseSection
                    fullPackage={fullPackage}
                    chapterPackages={chapterPackages}
                />
            </div>
        </>
    );
}
