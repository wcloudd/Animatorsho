export type CatalogPackage = {
    slug: string;
    title: string;
    priceToman: number;
    chapterNumber: number | null;
};

export type CheckoutCatalogProps = {
    fullPackage: CatalogPackage;
    chapterPackages: CatalogPackage[];
};

export type {
    OrderSummaryContent,
    OrderSummaryVariant,
} from '@/lib/checkout-confirm';
