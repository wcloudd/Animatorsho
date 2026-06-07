import { formatTomanPrice } from '@/lib/format-toman';
import { cn } from '@/lib/utils';

const FEATURED_PLAN_HEADER_GRADIENT =
    'linear-gradient(90deg, rgba(111,143,254,1) 0%, rgba(241,146,212,1) 50%, rgba(239,180,43,1) 100%)' as const;

const FEATURED_PLAN_BORDER_GRADIENT =
    'linear-gradient(135deg, rgba(111,143,254,1) 0%, rgba(96,55,168,1) 35%, rgba(235,162,57,1) 100%)' as const;

type PurchasePlanFeaturedCardProps = {
    title: string;
    priceToman: number;
    ctaLabel: string;
    ctaHref: string;
};

export function PurchasePlanFeaturedCard({
    title,
    priceToman,
    ctaLabel,
    ctaHref,
}: PurchasePlanFeaturedCardProps) {
    const formattedPrice = formatTomanPrice(priceToman);

    return (
        <article className="w-full">
            <div
                className="rounded-[28px] p-[3px]"
                style={{ backgroundImage: FEATURED_PLAN_BORDER_GRADIENT }}
            >
                <div className="overflow-hidden rounded-[26px] bg-surface">
                    <div
                        className="px-4 py-3 text-center text-sm font-bold text-white"
                        style={{
                            backgroundImage: FEATURED_PLAN_HEADER_GRADIENT,
                        }}
                    >
                        {title}
                    </div>

                    <div className="flex flex-col items-center gap-2 px-5 py-6 text-center">
                        <p className="text-base font-bold text-text">
                            پرداخت نقدی
                        </p>
                        <p className="text-2xl font-black text-text">
                            {formattedPrice}
                        </p>

                        <a
                            href={ctaHref}
                            className={cn(
                                'btn-cta-green mt-3 flex h-12 w-full max-w-[280px] items-center justify-center rounded-pill px-4 text-sm font-bold text-white',
                            )}
                        >
                            {ctaLabel}
                        </a>
                    </div>
                </div>
            </div>
        </article>
    );
}
