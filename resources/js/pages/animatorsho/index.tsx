import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Play, User } from 'lucide-react';
import { ProfileUserAvatar } from '@/components/profile/profile-user-avatar';
import { resolvePresetAvatar } from '@/lib/resolve-preset-avatar';
import { CourseChaptersSection } from '@/components/landing/course-chapters-section';
import { ConsultationCtaSection } from '@/components/landing/consultation-cta-section';
import { FinalCtaSection } from '@/components/landing/final-cta-section';
import { LandingFooter } from '@/components/landing/landing-footer';
import { FaqSection } from '@/components/landing/faq-section';
import { LandingMediaVideo } from '@/components/landing/landing-media-video';
import { LandingVideoModal } from '@/components/landing/landing-video-modal';
import { StudentWorksSection } from '@/components/landing/student-works-section';
import { CHECKOUT_FULL_URL } from '@/lib/checkout-urls';
import {
    LANDING_AFTER_REGISTRATION_MEDIA,
    LANDING_AI_SECTION_MEDIA,
    LANDING_COURSE_OVERVIEW_MEDIA,
    LANDING_HERO_CLICK_VIDEO_SRC,
    LANDING_HERO_MEDIA,
    LANDING_IN_PERSON_COURSE_CLICK_VIDEO_SRC,
    LANDING_IN_PERSON_COURSE_PREVIEW_MEDIA,
    LANDING_INSTRUCTOR_IMAGE,
    LANDING_INSTRUCTOR_PORTFOLIO_CLICK_VIDEO_SRC,
    LANDING_INSTRUCTOR_PORTFOLIO_PREVIEW_MEDIA,
    LANDING_MEET_MEDIA,
    LANDING_NIMVAJABEE_WORLD_MEDIA,
} from '@/lib/landing-media';
import { login, profile } from '@/routes';
import { SeoHead } from '@/components/seo/seo-head';
import { PUBLIC_PAGE_SEO, canonicalFromPath, defaultOpenGraph } from '@/lib/seo';
import type { HomeSeoProps, SharedPageProps } from '@/types/seo';

type AnimatorshoIndexProps = {
    seo?: HomeSeoProps;
};

const NIMVAJABEE_WORLD_CTA_URL = 'https://eitaa.com/nimvajabee/51' as const;

function scrollToSection(sectionId: string) {
    const element = document.getElementById(sectionId);
    if (!element) {
        return;
    }

    const scrollMarginTop =
        Number.parseFloat(getComputedStyle(element).scrollMarginTop) || 0;
    const top =
        element.getBoundingClientRect().top + window.scrollY + scrollMarginTop;

    window.scrollTo({
        top,
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? 'auto'
            : 'smooth',
    });
}

const heroHeaderActionClassName =
    'inline-flex min-w-0 items-center gap-1.5 rounded-pill border border-border bg-surface px-4 py-2 text-sm font-medium text-text transition-colors hover:bg-purple-soft';

function HeroHeader() {
    const { auth } = usePage().props;

    return (
        <header className="flex items-center justify-between rounded-2xl bg-surface px-4 py-3 shadow-soft ring-1 ring-border">
            <span className="font-display text-lg font-bold text-text">
                انیماتورشو
            </span>
            {auth.user ? (
                <Link
                    href={profile()}
                    className={heroHeaderActionClassName}
                    aria-label="پروفایل من"
                >
                    <ProfileUserAvatar
                        resolved={resolvePresetAvatar(
                            auth.user.avatar_preset as string | null | undefined,
                            auth.user.name,
                        )}
                        className="size-6 shrink-0"
                        fallbackClassName="text-[10px] font-bold text-purple bg-purple-soft"
                        emojiClassName="text-sm"
                    />
                    <span className="max-w-[7.5rem] truncate">
                        {auth.user.name}
                    </span>
                </Link>
            ) : (
                <Link href={login()} className={heroHeaderActionClassName}>
                    <User className="size-4 shrink-0 stroke-[1.75]" aria-hidden />
                    ورود
                </Link>
            )}
        </header>
    );
}

function HeroMediaCard() {
    const [open, setOpen] = useState(false);

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                aria-label="پخش ویدئوی معرفی دوره انیماتورشو"
                className="group relative w-full cursor-pointer border-0 bg-transparent p-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-black rounded-[20px]"
            >
                <LandingMediaVideo
                    videoSrc={LANDING_HERO_MEDIA.videoSrc}
                    posterSrc={LANDING_HERO_MEDIA.posterSrc}
                    ariaLabel={LANDING_HERO_MEDIA.ariaLabel}
                    aspectClassName="aspect-video"
                    placeholderVariant="video"
                    placeholderMessage="ویدیو معرفی به‌زودی"
                />
                <div aria-hidden className="pointer-events-none absolute inset-0 flex items-center justify-center rounded-[20px]">
                    <span className="flex h-14 w-14 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm transition-transform group-hover:scale-110">
                        <Play className="size-6 translate-x-0.5" fill="currentColor" stroke="none" />
                    </span>
                </div>
            </button>

            {open && (
                <LandingVideoModal
                    videoSrc={LANDING_HERO_CLICK_VIDEO_SRC}
                    ariaLabel="پخش ویدئوی معرفی دوره انیماتورشو"
                    onClose={() => setOpen(false)}
                />
            )}
        </>
    );
}

function MeetAnimatorshoSection() {
    return (
        <section
            id="course-intro"
            className="flex h-[760px] w-full scroll-mt-24 flex-col items-center justify-center gap-6 px-4 pt-[93px] pb-12 text-center"
            aria-labelledby="meet-animatorsho-heading"
        >
            <LandingMediaVideo
                videoSrc={LANDING_MEET_MEDIA.videoSrc}
                posterSrc={LANDING_MEET_MEDIA.posterSrc}
                ariaLabel={LANDING_MEET_MEDIA.ariaLabel}
                className="mx-auto w-[290px] shrink-0 rounded-2xl"
                aspectClassName="aspect-square"
            />

            <div className="flex flex-col items-center gap-3">
                <h2
                    id="meet-animatorsho-heading"
                    className="leading-tight"
                >
                    <span className="block text-[26px] font-medium text-text">
                        با{' '}
                        <span className="font-display text-[27px] font-black text-brand-hero-fill">
                            انیماتورشو
                        </span>
                    </span>
                    <span className="mt-1 block text-2xl font-medium text-text">
                        بیشتر آشناشو
                    </span>
                </h2>
                <p className="w-[197px] text-sm leading-[19px] text-[#999997]">
                    (
                    آموزش ساخت همین انیمیشن بالا
                    در ورکشاپ قرار گرفته و میتونی
                    یادبگیری و بسازیش!
                    )
                </p>
            </div>
        </section>
    );
}

function CourseOverviewSection() {
    return (
        <section
            id="course-overview"
            className="flex w-full scroll-mt-24 flex-col items-center gap-8 px-4 py-12 text-center"
            aria-labelledby="course-overview-heading"
        >
            <LandingMediaVideo
                videoSrc={LANDING_COURSE_OVERVIEW_MEDIA.videoSrc}
                posterSrc={LANDING_COURSE_OVERVIEW_MEDIA.posterSrc}
                ariaLabel={LANDING_COURSE_OVERVIEW_MEDIA.ariaLabel}
                aspectClassName="aspect-square"
            />

            <div className="flex w-full flex-col items-start gap-4">
                <h2
                    id="course-overview-heading"
                    className="font-display w-full text-[1.75rem] leading-tight font-bold text-text"
                >
                    <span className="block text-right text-black">ساخت انیمیشن</span>
                    <span className="mt-1 block text-right text-black">
                        از ایده تا اولین خروجی
                    </span>
                </h2>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    انیماتورشو یک دوره جامع و پروژه‌محور برای شروع ساخت
                    انیمیشن دوبعدی است؛ از یادگیری مبانی انیمیشن و طراحی
                    کاراکتر، تا ساخت اولین انیمیشن قابل انتشار. در این دوره
                    فقط با ابزارهای نرم‌افزاری آشنا نمی‌شوی؛ بلکه همراه با
                    مدرس، مسیر واقعی ساخت یک انیمیشن را قدم‌به‌قدم تجربه
                    می‌کنی.
                </p>
            </div>
        </section>
    );
}

function NimvajabeeWorldSection() {
    return (
        <section
            id="nimvajabee-world"
            className="flex w-full scroll-mt-24 flex-col items-center px-4 py-12 text-center"
            aria-labelledby="nimvajabee-world-heading"
        >
            <div className="relative w-full">
                <LandingMediaVideo
                    videoSrc={LANDING_NIMVAJABEE_WORLD_MEDIA.videoSrc}
                    posterSrc={LANDING_NIMVAJABEE_WORLD_MEDIA.posterSrc}
                    ariaLabel={LANDING_NIMVAJABEE_WORLD_MEDIA.ariaLabel}
                    aspectClassName="aspect-square"
                />
                <a
                    href={NIMVAJABEE_WORLD_CTA_URL}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="absolute bottom-0 left-1/2 z-10 flex h-12 -translate-x-1/2 translate-y-1/2 items-center justify-center rounded-pill bg-green px-6 text-base font-normal whitespace-nowrap text-white shadow-soft transition-opacity hover:opacity-95"
                >
                    ورود به دنیای نیم‌وجبی
                </a>
            </div>

            <div className="mt-10 flex w-full flex-col items-start justify-center gap-4 pt-[19px] pb-[20px]">
                <h2
                    id="nimvajabee-world-heading"
                    className="font-display text-[1.75rem] leading-tight font-bold text-text"
                >
                    یادگیری از خالق نیم‌وجبی
                </h2>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    انیماتورشو توسط خالق انیمیشن‌های نیم‌وجبی آموزش داده
                    می‌شود؛ کسی که خودش تجربه ساخت انیمیشن‌های دوبعدی ساده،
                    خطی و مناسب فضای مجازی را داشته است. تمرکز دوره فقط
                    آموزش نرم‌افزار نیست؛ بلکه یادگیری مسیر واقعی ساخت
                    انیمیشن، از ایده تا خروجی است.
                </p>
            </div>
        </section>
    );
}

function AfterRegistrationSection() {
    return (
        <section
            id="after-registration"
            className="flex w-full scroll-mt-24 flex-col items-center gap-8 px-4 py-12 text-center"
            aria-labelledby="after-registration-heading"
        >
            <LandingMediaVideo
                videoSrc={LANDING_AFTER_REGISTRATION_MEDIA.videoSrc}
                posterSrc={LANDING_AFTER_REGISTRATION_MEDIA.posterSrc}
                ariaLabel={LANDING_AFTER_REGISTRATION_MEDIA.ariaLabel}
                aspectClassName="aspect-square"
            />

            <div className="flex w-full flex-col items-start gap-[25px]">
                <h2
                    id="after-registration-heading"
                    className="font-display w-full text-[1.75rem] leading-tight font-bold text-text"
                >
                    <span className="block text-right text-black">بعد از ثبت‌نام</span>
                    <span className="mt-1 block text-right text-black">
                        چه اتفاقی می‌افته؟
                    </span>
                </h2>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    بعد از تکمیل مراحل ثبت‌نام، لایسنس دوره در پنل کاربری شما
                    قرار می‌گیرد و با فعال‌سازی آن، دسترسی کامل به محتوای دوره
                    از طریق SpotPlayer برایتان فعال می‌شود. در کنار مشاهده
                    جلسات، امکان ارتباط با پشتیبان و پیگیری مسیر یادگیری هم
                    برای شما فراهم است؛ تا اگر در اجرای تمرین‌ها یا شروع مسیر
                    سوالی داشتید، تنها نمانید.
                </p>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    هدف این است که فقط بیننده آموزش نباشید؛ بلکه همراه با
                    مسیری که مدرس دوره برایتان تعریف کرده، جلسات را ببینید،
                    تمرین کنید و قدم‌به‌قدم به ساخت اولین انیمیشن خودتان
                    نزدیک شوید.
                </p>
            </div>
        </section>
    );
}

function InstructorSection() {
    const [portfolioOpen, setPortfolioOpen] = useState(false);

    return (
        <section
            id="instructor"
            className="flex w-full scroll-mt-24 flex-col items-center gap-8 px-4 py-12 text-center"
            aria-labelledby="instructor-heading"
        >
            <h2
                id="instructor-heading"
                className="font-display text-[1.75rem] leading-tight font-bold text-text"
            >
                آشنایی با مدرس دوره
            </h2>

            <LandingMediaVideo
                videoSrc=""
                posterSrc={LANDING_INSTRUCTOR_IMAGE.src}
                ariaLabel={LANDING_INSTRUCTOR_IMAGE.ariaLabel}
                className="mx-auto w-[200px] shrink-0"
                aspectClassName="aspect-square"
                enabled={false}
                placeholderVariant="default"
                placeholderMessage="تصویر مدرس به‌زودی"
            />

            <div className="flex w-full flex-col items-start gap-4 text-right">
                <p className="max-w-[354px] text-sm font-medium leading-relaxed text-[#646464]">
                    سلام! من ابوالفضل رستگارمقدم هستم؛ سازنده‌ی انیمیشن‌های نیم‌وجبی و مدرس دوره‌ی انیماتورشو.
                </p>
                <p className="max-w-[354px] text-sm font-medium leading-relaxed text-[#646464]">
                    بیش از ۱۱ ساله که در دنیای انیمیشن، موشن‌گرافیک، کارهای دوبعدی و سه‌بعدی و طراحی‌های تصویری فعالیت می‌کنم. بعد از سال‌ها تجربه و آزمون‌وخطا، تصمیم گرفتم چیزهایی رو که یاد گرفتم و واقعاً در مسیر ساخت انیمیشن به درد می‌خوره، داخل یک دوره‌ی جامع به اسم «انیماتورشو» جمع کنم.
                </p>
                <p className="max-w-[354px] text-sm font-medium leading-relaxed text-[#646464]">
                    اینجا قرار نیست فقط چندتا دکمه و ابزار یاد بگیری؛ قراره با هم ایده بسازیم، کاراکتر طراحی کنیم، حرکت بدیم، تمرین کنیم، اشتباه کنیم، بخندیم و در نهایت به یک انیمیشن واقعی و قابل انتشار برسیم.
                </p>
            </div>

            <div className="flex w-full flex-col items-start gap-4">
                <h3 className="font-display w-full text-right text-xl font-bold text-text">
                    بخشی از نمونه‌کارهای من
                </h3>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    توی این ویدئو می‌تونی چند نمونه از کارهایی که در سال‌های گذشته ساختم رو ببینی؛ از موشن‌گرافیک‌های دوبعدی و سه‌بعدی گرفته تا طراحی‌های تبلیغاتی و تولیدات تصویری.
                </p>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    هدفم از نمایش این نمونه‌ها اینه که بدونی انیماتورشو فقط یک آموزش نرم‌افزاری نیست؛ پشت این دوره تجربه‌ی واقعی تولید، اجرا و ساخت پروژه‌های مختلف قرار داره.
                </p>

                <button
                    type="button"
                    onClick={() => setPortfolioOpen(true)}
                    aria-label="پخش نمونه‌کارهای مدرس"
                    className="group relative w-full cursor-pointer border-0 bg-transparent p-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-black rounded-[20px]"
                >
                    <LandingMediaVideo
                        videoSrc={LANDING_INSTRUCTOR_PORTFOLIO_PREVIEW_MEDIA.videoSrc}
                        posterSrc={LANDING_INSTRUCTOR_PORTFOLIO_PREVIEW_MEDIA.posterSrc}
                        ariaLabel={LANDING_INSTRUCTOR_PORTFOLIO_PREVIEW_MEDIA.ariaLabel}
                        aspectClassName="aspect-video"
                        placeholderVariant="video"
                        placeholderMessage="ویدیو نمونه‌کارها به‌زودی"
                    />
                    <div aria-hidden className="pointer-events-none absolute inset-0 flex items-center justify-center rounded-[20px]">
                        <span className="flex h-14 w-14 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm transition-transform group-hover:scale-110">
                            <Play className="size-6 translate-x-0.5" fill="currentColor" stroke="none" />
                        </span>
                    </div>
                </button>
            </div>

            {portfolioOpen && (
                <LandingVideoModal
                    videoSrc={LANDING_INSTRUCTOR_PORTFOLIO_CLICK_VIDEO_SRC}
                    ariaLabel="پخش نمونه‌کارهای ابوالفضل رستگارمقدم"
                    onClose={() => setPortfolioOpen(false)}
                />
            )}
        </section>
    );
}

function InPersonCourseSection() {
    const [open, setOpen] = useState(false);

    return (
        <section
            id="in-person-course"
            className="flex w-full scroll-mt-24 flex-col items-center gap-8 px-4 py-12 text-center"
            aria-labelledby="in-person-course-heading"
        >
            <div className="flex w-full flex-col items-start gap-4">
                <h2
                    id="in-person-course-heading"
                    className="font-display w-full text-right text-[1.75rem] leading-tight font-bold text-text"
                >
                    دوره حضوری
                </h2>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    انیماتورشو فقط یک دوره‌ی آنلاین نیست؛ بخشی از مسیرش از دل کلاس‌ها و تجربه‌های واقعی شکل گرفته.
                </p>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    در این ویدئو، گزارشی کوتاه از دوره‌ی حضوری ۸ روزه‌ی آموزش انیمیشن‌سازی با گوشی رو می‌بینی؛ دوره‌ای که هنرجوها قدم‌به‌قدم از ایده‌پردازی و طراحی ساده شروع کردند و با گوشی، اولین تجربه‌های انیمیشن‌سازی خودشون رو ساختند.
                </p>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    این تجربه‌ی حضوری کمک کرد مسیر آموزش انیماتورشو کاربردی‌تر، ساده‌تر و نزدیک‌تر به نیاز هنرجوها طراحی بشه.
                </p>
            </div>

            <button
                type="button"
                onClick={() => setOpen(true)}
                aria-label="پخش گزارش دوره حضوری ۸ روزه"
                className="group relative w-full cursor-pointer border-0 bg-transparent p-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-black rounded-[20px]"
            >
                <LandingMediaVideo
                    videoSrc={LANDING_IN_PERSON_COURSE_PREVIEW_MEDIA.videoSrc}
                    posterSrc={LANDING_IN_PERSON_COURSE_PREVIEW_MEDIA.posterSrc}
                    ariaLabel={LANDING_IN_PERSON_COURSE_PREVIEW_MEDIA.ariaLabel}
                    aspectClassName="aspect-video"
                    placeholderVariant="video"
                    placeholderMessage="ویدیو دوره حضوری به‌زودی"
                />
                <div aria-hidden className="pointer-events-none absolute inset-0 flex items-center justify-center rounded-[20px]">
                    <span className="flex h-14 w-14 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm transition-transform group-hover:scale-110">
                        <Play className="size-6 translate-x-0.5" fill="currentColor" stroke="none" />
                    </span>
                </div>
            </button>

            {open && (
                <LandingVideoModal
                    videoSrc={LANDING_IN_PERSON_COURSE_CLICK_VIDEO_SRC}
                    ariaLabel="پخش گزارش دوره حضوری ۸ روزه"
                    onClose={() => setOpen(false)}
                />
            )}
        </section>
    );
}

function AiAnimationSection() {
    return (
        <section
            id="ai-animation"
            className="flex w-full scroll-mt-24 flex-col items-center gap-8 px-4 py-12 text-center"
            aria-labelledby="ai-animation-heading"
        >
            <LandingMediaVideo
                videoSrc={LANDING_AI_SECTION_MEDIA.videoSrc}
                posterSrc={LANDING_AI_SECTION_MEDIA.posterSrc}
                ariaLabel={LANDING_AI_SECTION_MEDIA.ariaLabel}
                aspectClassName="aspect-square"
                placeholderVariant="video"
                placeholderMessage="ویدیو هوش مصنوعی به‌زودی"
            />

            <div className="flex w-full flex-col items-start gap-4">
                <h2
                    id="ai-animation-heading"
                    className="font-display w-full text-right text-[1.75rem] leading-tight font-bold text-text"
                >
                    جای هوش مصنوعی کجاست؟
                </h2>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    هوش مصنوعی یه ابزاره، نه یه میان‌بُر برای انیماتور شدن. ممکنه تو بعضی بخش‌ها کمک‌کننده باشه، اما جای مهارت، تجربه و نگاه هنری رو نمی‌گیره.
                </p>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    اگر هنوز ایده‌پردازی، طراحی، حرکت، زمان‌بندی و روایت رو بلد نباشی، هوش مصنوعی نمی‌تونه ازت انیماتور بسازه؛ چون خروجی خوب فقط از ابزار نمیاد، از نگاه، تجربه و تصمیم‌های درستِ سازنده میاد. بدون این مهارت‌ها، حتی اگر خروجی جذابی هم بگیری، نمی‌دونی چرا خوب شده، کجاش ایراد داره و چطور باید بهترش کنی.
                </p>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    توی انیماتورشو اول یاد می‌گیری خودت بسازی؛ بفهمی حرکت چطوری کار می‌کنه، چطور یه ایده رو تبدیل به تصویر و انیمیشن کنی و چطور با فکر و سلیقه‌ی خودت به یک خروجی واقعی برسی.
                </p>
                <p className="max-w-[354px] text-right text-sm font-medium leading-relaxed text-[#646464]">
                    چون انیمیشنی که با دست، فکر، تمرین و نگاه شخصی تو ساخته می‌شه، چیزی داره که هیچ ابزار آماده‌ای کامل جاش رو نمی‌گیره: امضای شخصی تو.
                </p>
            </div>
        </section>
    );
}

export default function AnimatorshoIndex({ seo }: AnimatorshoIndexProps) {
    const { appUrl } = usePage<SharedPageProps>().props;
    const meta = PUBLIC_PAGE_SEO.home;
    const jsonLd = seo
        ? [seo.organization, ...(seo.course ? [seo.course] : [])]
        : undefined;

    return (
        <>
            <SeoHead
                title={meta.title}
                description={meta.description}
                canonical={canonicalFromPath(appUrl, '/')}
                openGraph={{
                    ...defaultOpenGraph(appUrl, {
                        title: meta.title,
                        description: meta.description,
                        path: '/',
                    }),
                    image: seo?.ogImage,
                }}
                jsonLd={jsonLd}
            />
            <div className="w-full bg-bg">
                <div className="mx-auto flex w-full max-w-[390px] flex-col gap-5 px-4 pt-4 pb-8">
                    <HeroHeader />
                    <HeroMediaCard />

                    <section className="flex flex-col items-center gap-4 text-center">
                        <h1 className="font-display text-[1.75rem] leading-tight font-bold">
                            <span className="block text-gradient-animate">
                                یادگیری ساخت انیمیشن
                            </span>
                            <span className="mt-1 block text-text">
                                ساده‌تر از همیشه!
                            </span>
                        </h1>
                        <p className="max-w-[270px] text-base leading-relaxed text-[#918f8c]">
                            از طراحی شخصیت تا گرفتن اولین خروجی، با یک مسیر
                            ساده و قابل انجام
                        </p>

                        <div className="flex w-full flex-col gap-3 pt-1">
                            <a
                                href="#course-intro"
                                onClick={(event) => {
                                    event.preventDefault();
                                    scrollToSection('course-intro');
                                }}
                                className="btn-rainbow-stroke flex h-12 w-full items-center justify-center rounded-pill text-base font-bold text-text shadow-soft transition-opacity hover:opacity-95"
                            >
                                آشنایی با انیماتورشو
                            </a>
                            <Link
                                href={CHECKOUT_FULL_URL}
                                className="flex h-12 w-full items-center justify-center rounded-pill bg-surface text-base font-medium text-muted shadow-soft ring-1 ring-border transition-colors hover:bg-purple-soft hover:text-text"
                            >
                                ثبت نام در دوره انیماتورشو
                            </Link>
                        </div>
                    </section>
                </div>
            </div>

            <div className="w-full bg-surface">
                <div className="mx-auto flex w-full min-w-0 max-w-[390px] flex-col overflow-x-hidden">
                    <MeetAnimatorshoSection />
                    <CourseOverviewSection />
                    <NimvajabeeWorldSection />
                    <InstructorSection />
                    <InPersonCourseSection />
                    <CourseChaptersSection />
                    <AiAnimationSection />
                    <AfterRegistrationSection />
                    <StudentWorksSection />
                    <FaqSection />
                    <ConsultationCtaSection />
                </div>
            </div>

            <FinalCtaSection />
            <LandingFooter />
        </>
    );
}
