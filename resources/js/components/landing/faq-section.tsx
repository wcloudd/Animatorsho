import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import { LandingMediaImage } from '@/components/landing/landing-media-image';
import { LANDING_FAQ_IMAGE } from '@/lib/landing-media';
import { cn } from '@/lib/utils';

type FaqItem = {
    id: string;
    question: string;
    answer: string;
};

const FAQ_ITEMS = [
    {
        id: 'faq-design',
        question: 'برای شروع باید طراحی بلد باشم؟',
        answer:
            'نه. دوره از پایه شروع می‌شود و قدم‌به‌قدم با طراحی ساده، ابزارها و ساخت انیمیشن آشنا می‌شوی.',
    },
    {
        id: 'faq-access',
        question: 'دوره را کجا می‌بینم؟',
        answer:
            'بعد از ثبت‌نام، لایسنس دوره در پنل کاربری‌ات قرار می‌گیرد و جلسات از طریق SpotPlayer قابل مشاهده است.',
    },
    {
        id: 'faq-mobile',
        question: 'با گوشی هم می‌شود انیمیشن ساخت؟',
        answer:
            'بله. یکی از بخش‌های دوره مخصوص ساخت انیمیشن با گوشی است و مسیر ساده‌تری برای شروع ارائه می‌دهد.',
    },
    {
        id: 'faq-support',
        question: 'اگر در تمرین‌ها گیر کردم چی؟',
        answer:
            'می‌توانی از طریق پشتیبانی سوالت را مطرح کنی و مسیرت را پیگیری کنی.',
    },
    {
        id: 'faq-age',
        question: 'دوره برای چه سنی مناسبه؟',
        answer:
            'برای نوجوان‌ها و جوان‌هایی که می‌خواهند ساخت انیمیشن را از صفر و به شکل عملی شروع کنند مناسب است.',
    },
    {
        id: 'faq-installment',
        question: 'خرید اقساطی هم دارید؟',
        answer:
            'بله، امکان درخواست خرید اقساطی وجود دارد و بعد از بررسی، مسیر ثبت‌نام مرحله‌ای برای شما توضیح داده می‌شود.',
    },
] as const satisfies readonly FaqItem[];

function FaqAccordionItem({
    item,
    isOpen,
    onToggle,
}: {
    item: FaqItem;
    isOpen: boolean;
    onToggle: () => void;
}) {
    const answerId = `${item.id}-answer`;

    return (
        <div className="overflow-hidden rounded-2xl bg-surface ring-1 ring-border">
            <button
                type="button"
                id={item.id}
                aria-expanded={isOpen}
                aria-controls={answerId}
                onClick={onToggle}
                className="flex w-full items-start justify-between gap-3 px-4 py-4 text-right transition-colors hover:bg-purple-soft/30"
            >
                <span className="flex-1 text-base font-bold leading-snug text-text">
                    {item.question}
                </span>
                <ChevronDown
                    className={cn(
                        'mt-0.5 size-5 shrink-0 text-muted transition-transform duration-200',
                        isOpen && 'rotate-180',
                    )}
                    aria-hidden
                />
            </button>
            {isOpen ? (
                <div
                    id={answerId}
                    role="region"
                    aria-labelledby={item.id}
                    className="px-4 pb-4 text-right text-sm leading-relaxed text-[#646464]"
                >
                    {item.answer}
                </div>
            ) : null}
        </div>
    );
}

export function FaqSection() {
    const [openId, setOpenId] = useState<string | null>(null);

    function toggleItem(id: string) {
        setOpenId((current) => (current === id ? null : id));
    }

    return (
        <section
            id="faq"
            className="flex w-full scroll-mt-24 flex-col gap-8 px-4 py-12"
            aria-labelledby="faq-heading"
        >
            <LandingMediaImage
                src={LANDING_FAQ_IMAGE.src}
                ariaLabel={LANDING_FAQ_IMAGE.ariaLabel}
                className="aspect-[4/3] w-full overflow-hidden rounded-[32px] bg-surface"
                imageClassName="block h-full w-full object-cover"
            />

            <div className="flex w-full flex-col items-start gap-3">
                <h2
                    id="faq-heading"
                    className="font-display w-full text-right text-[1.75rem] leading-tight font-bold text-black"
                >
                    سوالات پرتکرار
                </h2>
                <p className="text-right text-sm font-medium leading-relaxed text-[#646464]">
                    چند جواب کوتاه برای تصمیم‌گیری راحت‌تر
                </p>
            </div>

            <div className="flex w-full flex-col gap-3">
                {FAQ_ITEMS.map((item) => (
                    <FaqAccordionItem
                        key={item.id}
                        item={item}
                        isOpen={openId === item.id}
                        onToggle={() => toggleItem(item.id)}
                    />
                ))}
            </div>
        </section>
    );
}
