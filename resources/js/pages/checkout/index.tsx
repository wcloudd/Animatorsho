import { usePage } from '@inertiajs/react';
import { PurchaseSection } from '@/components/landing/purchase-section';
import { PurchasesDisabledNotice } from '@/components/checkout/purchases-disabled-notice';
import { SeoHead } from '@/components/seo/seo-head';
import type { CheckoutCatalogProps } from '@/lib/checkout-catalog';
import { PUBLIC_PAGE_SEO, canonicalFromPath } from '@/lib/seo';
import type { SharedPageProps } from '@/types/seo';

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
    const { appUrl } = usePage<SharedPageProps>().props;
    const meta = PUBLIC_PAGE_SEO.checkout;

    return (
        <>
            <SeoHead
                title={meta.title}
                description={meta.description}
                canonical={canonicalFromPath(appUrl, '/checkout')}
            />
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
