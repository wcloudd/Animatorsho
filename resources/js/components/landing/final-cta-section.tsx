import { Phone } from 'lucide-react';
import type { MouseEvent, ReactNode } from 'react';
import { LandingMediaImage } from '@/components/landing/landing-media-image';
import { CHECKOUT_FULL_URL } from '@/lib/checkout-urls';
import { LANDING_FINAL_CTA_IMAGE } from '@/lib/landing-media';

const FINAL_CTA_PLANS_HREF = CHECKOUT_FULL_URL;

const FINAL_CTA_EITAA_URL =
    'https://web.eitaa.com/#@admin_nimvajabee' as const;

const FINAL_CTA_PHONE = '09014763601' as const;

const FINAL_CTA_PHONE_TEL = 'tel:+989014763601' as const;

function openPhoneDialer(
    event: MouseEvent<HTMLAnchorElement>,
    href: string,
): void {
    event.preventDefault();
    window.location.assign(href);
}

function EitaaIcon({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={className}
            aria-hidden
        >
            <circle
                cx="12"
                cy="12"
                r="10"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.5"
            />
            <path
                fill="currentColor"
                d="M8.5 7.5h5.2c2.1 0 3.3 1.1 3.3 2.8 0 1.2-.6 2.1-1.6 2.5l2.1 3.7h-2.4l-1.8-3.2h-2.6v3.2H8.5V7.5zm2.4 4.6h2.5c.9 0 1.4-.4 1.4-1.1s-.5-1.1-1.4-1.1h-2.5v2.2z"
            />
        </svg>
    );
}

type GlassContactButtonProps = {
    href: string;
    icon: ReactNode;
    title: string;
    subtitle: string;
    external?: boolean;
    opensPhoneDialer?: boolean;
};

function GlassContactButton({
    href,
    icon,
    title,
    subtitle,
    external = false,
    opensPhoneDialer = false,
}: GlassContactButtonProps) {
    return (
        <a
            href={href}
            {...(external
                ? { target: '_blank', rel: 'noopener noreferrer' }
                : {})}
            {...(opensPhoneDialer
                ? {
                      'aria-label': `تماس با ${subtitle}`,
                      onClick: (event: MouseEvent<HTMLAnchorElement>) =>
                          openPhoneDialer(event, href),
                  }
                : {})}
            className="flex min-w-0 flex-1 items-center gap-2 rounded-2xl border border-white/70 bg-black/25 px-2.5 py-2.5 text-white backdrop-blur-sm transition-opacity hover:opacity-95"
        >
            <div className="flex min-w-0 flex-1 flex-col items-start gap-0.5 text-right leading-tight">
                <span className="text-[11px] font-bold">{title}</span>
                <span className="text-[10px] font-medium text-white/85">
                    {subtitle}
                </span>
            </div>
            {icon}
        </a>
    );
}

export function FinalCtaSection() {
    return (
        <section
            id="final-cta"
            className="relative flex w-full min-h-[828px] scroll-mt-24 flex-col items-center justify-center overflow-hidden"
            aria-labelledby="final-cta-heading"
        >
            <LandingMediaImage
                src={LANDING_FINAL_CTA_IMAGE.src}
                ariaLabel={LANDING_FINAL_CTA_IMAGE.ariaLabel}
                className="absolute inset-0"
                imageClassName="absolute inset-0 h-full w-full object-cover"
                placeholderVariant="dark"
            />
            <div
                className="absolute inset-0 bg-black/45"
                aria-hidden
            />

            <div className="relative z-10 flex w-full flex-col items-center gap-8 px-5 py-14 text-center">
                <h2
                    id="final-cta-heading"
                    className="max-w-[320px] text-[1.625rem] leading-[1.35] font-bold text-white"
                >
                    <span className="block">یک شروع جدی</span>
                    <span className="block">برای ورود به دنیای</span>
                    <span className="block">انیمیشن‌سازی</span>
                </h2>

                <div className="final-cta-register relative w-full max-w-[340px]">
                    <a
                        href={FINAL_CTA_PLANS_HREF}
                        className="font-display relative z-10 flex h-12 w-full items-center justify-center rounded-pill bg-white px-4 text-lg font-bold transition-opacity hover:opacity-95"
                    >
                        <span className="text-gradient-animate font-black">
                            ثبت‌نام دوره انیماتورشو
                        </span>
                    </a>
                </div>

                <div className="flex w-[330px] max-w-[358px] flex-row gap-[42px]">
                    <GlassContactButton
                        href={FINAL_CTA_EITAA_URL}
                        external
                        icon={<EitaaIcon className="size-5 shrink-0 text-white" />}
                        title="ارتباط با پشتیبان"
                        subtitle="در پیام‌رسان ایتا"
                    />
                    <GlassContactButton
                        href={FINAL_CTA_PHONE_TEL}
                        opensPhoneDialer
                        icon={
                            <Phone
                                className="size-5 shrink-0 stroke-[1.75] text-white"
                                aria-hidden
                            />
                        }
                        title="شماره تماس"
                        subtitle={FINAL_CTA_PHONE}
                    />
                </div>
            </div>
        </section>
    );
}
