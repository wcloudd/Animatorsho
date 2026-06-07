import { Head } from '@inertiajs/react';
import { PurchaseSection } from '@/components/landing/purchase-section';
import type { CheckoutCatalogProps } from '@/lib/checkout-catalog';

export default function CheckoutIndex({
    fullPackage,
    chapterPackages,
}: CheckoutCatalogProps) {
    return (
        <>
            <Head title="ثبت‌نام دوره" />
            <div className="pb-32">
                <PurchaseSection
                    fullPackage={fullPackage}
                    chapterPackages={chapterPackages}
                />
            </div>
        </>
    );
}
