import { Head, Link } from '@inertiajs/react';
import { ResultOrderSummaryPlaceholder } from '@/components/checkout/result-order-summary-placeholder';
import { ResultStatusCard } from '@/components/checkout/result-status-card';
import { TrustNote } from '@/components/checkout/trust-note';
import { PageContainer } from '@/components/page-container';
import { useCheckoutResultQuery } from '@/hooks/use-checkout-result-query';
import { cn } from '@/lib/utils';

export default function CheckoutResult() {
    const { content, orderNumber } = useCheckoutResultQuery();

    return (
        <>
            <Head title="نتیجه پرداخت" />
            <PageContainer>
                <div className="flex flex-col gap-6">
                    <header className="flex flex-col gap-3 text-center">
                        <ResultStatusCard content={content} />

                        <h1 className="font-display text-2xl font-bold text-text">
                            {content.title}
                        </h1>
                        <p className="text-sm font-medium leading-relaxed text-muted">
                            {content.description}
                        </p>
                    </header>

                    <ResultOrderSummaryPlaceholder
                        content={content}
                        orderNumber={orderNumber}
                    />

                    <div className="flex flex-col gap-3">
                        <Link
                            href={content.primaryCtaHref}
                            className={cn(
                                'btn-cta-green flex h-12 w-full items-center justify-center rounded-pill px-4 text-sm font-bold text-white',
                            )}
                        >
                            {content.primaryCtaLabel}
                        </Link>

                        <Link
                            href={content.secondaryCtaHref}
                            className={cn(
                                'flex h-12 w-full items-center justify-center rounded-pill bg-surface text-sm font-bold text-text shadow-soft ring-1 ring-border transition-colors hover:bg-purple-soft',
                            )}
                        >
                            {content.secondaryCtaLabel}
                        </Link>
                    </div>

                    <TrustNote />
                </div>
            </PageContainer>
        </>
    );
}
