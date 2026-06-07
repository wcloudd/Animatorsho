import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { consultation } from '@/routes';
import support from '@/routes/support';
import { cn } from '@/lib/utils';

const CONSULTATION_IMAGE_SRC =
    '/media/landing/posters/consultation-section.webp';

function ConsultationMediaImage() {
    const [imageFailed, setImageFailed] = useState(false);

    const shellClass =
        'aspect-[4/3] w-full overflow-hidden rounded-[32px] bg-surface';

    if (imageFailed) {
        return (
            <div
                className={cn(shellClass, 'bg-[#f0f7f9]')}
                aria-label="تصویر بخش مشاوره رایگان"
            />
        );
    }

    return (
        <div className={shellClass}>
            <img
                src={CONSULTATION_IMAGE_SRC}
                alt=""
                className="block h-full w-full object-cover"
                loading="lazy"
                decoding="async"
                onError={() => setImageFailed(true)}
            />
        </div>
    );
}

export function ConsultationCtaSection() {
    return (
        <section
            id="consultation-cta"
            className="flex w-full scroll-mt-24 flex-col gap-8 px-4 py-12"
            aria-labelledby="consultation-cta-heading"
        >
            <ConsultationMediaImage />

            <div className="flex w-full flex-col items-start gap-[22px]">
                <h2
                    id="consultation-cta-heading"
                    className="font-display w-full text-right text-[1.75rem] leading-tight font-bold text-black"
                >
                    نیاز به راهنمایی داری؟
                </h2>
                <p className="text-right text-sm font-medium leading-relaxed text-[#646464]">
                    اگر نمی‌دانی این دوره برای سطح فعلی تو مناسب است یا نه،
                    قبل از ثبت‌نام درخواست مشاوره رایگان بفرست تا مسیر مناسب
                    شروع انیمیشن را پیدا کنی.
                </p>
            </div>

            <div className="flex w-full flex-row gap-3">
                <Link
                    href={consultation()}
                    className="flex h-12 min-w-0 flex-1 items-center justify-center rounded-pill bg-green px-3 text-sm font-bold text-white shadow-soft transition-opacity hover:opacity-95"
                >
                    دریافت مشاوره رایگان
                </Link>
                <Link
                    href={support.index()}
                    className="flex h-12 min-w-0 flex-1 items-center justify-center rounded-pill bg-surface px-3 text-sm font-bold text-green shadow-soft transition-opacity hover:opacity-95"
                >
                    پرسیدن سوال
                </Link>
            </div>
        </section>
    );
}
