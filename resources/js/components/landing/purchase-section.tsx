import { PurchasePlanFeaturedCard } from '@/components/landing/purchase-plan-featured-card';
import { PurchasePlanInfoCard } from '@/components/landing/purchase-plan-info-card';
import { PurchaseSectionIllustration } from '@/components/landing/purchase-section-illustration';
import type { CatalogPackage } from '@/lib/checkout-catalog';
import {
    CHECKOUT_CASH_URL,
    CHECKOUT_CHAPTER_URL,
    CHECKOUT_INSTALLMENT_URL,
} from '@/lib/checkout-urls';

const PURCHASE_CTA_HREFS = {
    cash: CHECKOUT_CASH_URL,
    installment: CHECKOUT_INSTALLMENT_URL,
    chapter: CHECKOUT_CHAPTER_URL,
} as const;

type PurchaseSectionProps = {
    fullPackage: CatalogPackage;
    chapterPackages: CatalogPackage[];
};

export function PurchaseSection({
    fullPackage,
    chapterPackages,
}: PurchaseSectionProps) {
    return (
        <section
            id="plans"
            className="flex w-full scroll-mt-24 flex-col items-center gap-8 bg-bg px-4 py-12"
            aria-labelledby="purchase-section-heading"
        >
            <div className="mx-auto flex w-full max-w-[354px] flex-col items-center gap-8">
                <PurchaseSectionIllustration />

                <div className="flex w-full flex-col items-center gap-4 text-center">
                    <h2
                        id="purchase-section-heading"
                        className="w-[322px] font-display text-[1.75rem] leading-tight font-bold text-black"
                    >
                        قفل دنیای انیمیشن سازی رو با انیماتورشو باز کن
                    </h2>
                    <p className="text-sm font-medium leading-relaxed text-[#646464]">
                        با دوره جامع انیماتورشو، به فصل‌های اصلی، ورکشاپ‌های
                        تکمیلی، آپدیت‌های دوره و مسیر ساخت اولین خروجی واقعی
                        دسترسی داری؛ به‌همراه کتاب دیجیتال، تمرین‌ها و
                        ورکشاپ‌های مرحله‌به‌مرحله.
                    </p>
                </div>

                <div className="flex w-full flex-col gap-4">
                    <PurchasePlanFeaturedCard
                        title={fullPackage.title}
                        priceToman={fullPackage.priceToman}
                        ctaLabel="ثبت‌نام نقدی"
                        ctaHref={PURCHASE_CTA_HREFS.cash}
                    />

                    <PurchasePlanInfoCard
                        title="پرداخت اقساطی"
                        ctaLabel="درخواست اقساطی"
                        ctaHref={PURCHASE_CTA_HREFS.installment}
                        ctaVariant="secondary"
                    >
                        <p className="font-bold text-text">
                            40% پیش پرداخت / مابقی اقساط
                        </p>
                        <p>اقساطی 1 الی 2 ماهه</p>
                    </PurchasePlanInfoCard>

                    <PurchasePlanInfoCard
                        title="خرید فصل‌ها به صورت جداگانه"
                        ctaLabel="انتخاب فصل"
                        ctaHref={PURCHASE_CTA_HREFS.chapter}
                        ctaVariant="outline"
                    >
                        <p>
                            یکی از فصل‌های دوره رو جداگانه انتخاب کن و فقط
                            همون بخش رو شروع کن. مناسب برای وقتی که می‌خوای
                            سبک‌تر وارد مسیر انیمیشن‌سازی بشی.
                        </p>
                        {chapterPackages.length > 0 ? (
                            <ul className="mt-2 flex flex-col gap-1 text-start text-xs font-medium text-muted">
                                {chapterPackages.map((chapter) => (
                                    <li key={chapter.slug}>
                                        {chapter.title}
                                    </li>
                                ))}
                            </ul>
                        ) : null}
                    </PurchasePlanInfoCard>
                </div>

                <p
                    id="purchase-rules"
                    className="scroll-mt-24 text-center text-xs font-medium leading-relaxed text-muted"
                >
                    *قبل از فعال‌سازی نهایی دسترسی، اگر درباره مناسب بودن دوره
                    سوالی داری، می‌تونی از مشاوره رایگان استفاده کنی. شرایط
                    ثبت‌نام، دسترسی SpotPlayer و درخواست‌های خاص مثل اقساطی،
                    شفاف بررسی می‌شن.
                </p>
            </div>
        </section>
    );
}
