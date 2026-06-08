import { Link, usePage } from '@inertiajs/react';
import { User } from 'lucide-react';
import { useState } from 'react';
import { ProfileUserAvatar } from '@/components/profile/profile-user-avatar';
import { resolvePresetAvatar } from '@/lib/resolve-preset-avatar';
import { CourseChaptersSection } from '@/components/landing/course-chapters-section';
import { ConsultationCtaSection } from '@/components/landing/consultation-cta-section';
import { FinalCtaSection } from '@/components/landing/final-cta-section';
import { LandingFooter } from '@/components/landing/landing-footer';
import { FaqSection } from '@/components/landing/faq-section';
import { LandingMediaVideo } from '@/components/landing/landing-media-video';
import { StudentWorksSection } from '@/components/landing/student-works-section';
import { CHECKOUT_FULL_URL } from '@/lib/checkout-urls';
import { login, profile } from '@/routes';
import { SeoHead } from '@/components/seo/seo-head';
import { PUBLIC_PAGE_SEO, canonicalFromPath, defaultOpenGraph } from '@/lib/seo';
import { cn } from '@/lib/utils';
import type { HomeSeoProps, SharedPageProps } from '@/types/seo';

type AnimatorshoIndexProps = {
    seo?: HomeSeoProps;
};

const HERO_VIDEO_SRC = '/videos/animatorsho-hero.mp4';
const HERO_POSTER_SRC = '/images/animatorsho/hero-poster.webp';

const MEET_MEDIA = {
    videoSrc: '/videos/animatorsho-meet.mp4',
    posterSrc: '/media/landing/posters/meet-intro.webp',
} as const;

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

const COURSE_OVERVIEW_MEDIA = {
    videoSrc: '/media/landing/videos/course-intro.mp4',
    posterSrc: '/media/landing/posters/course-intro.webp',
} as const;

const NIMVAJABEE_WORLD_MEDIA = {
    videoSrc: '/media/landing/videos/nimvajabee-world.mp4',
    posterSrc: '/media/landing/posters/nimvajabee-world.webp',
} as const;

const AFTER_REGISTRATION_MEDIA = {
    videoSrc: '/media/landing/videos/after-registration.mp4',
    posterSrc: '/media/landing/posters/after-registration.webp',
} as const;

const NIMVAJABEE_WORLD_CTA_URL = 'https://eitaa.com/nimvajabee/51' as const;

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
    const [mediaState, setMediaState] = useState<'video' | 'poster' | 'placeholder'>(
        'video',
    );

    const shellClass =
        'aspect-[4/3] w-full overflow-hidden rounded-[32px] bg-surface shadow-soft ring-1 ring-border';

    if (mediaState === 'placeholder') {
        return (
            <div
                className={cn(
                    shellClass,
                    'flex flex-col items-center justify-center gap-2 bg-gradient-to-br from-purple-soft via-surface to-gold-soft',
                )}
                aria-label="پیش‌نمایش ویدیو معرفی دوره"
            >
                <div className="flex size-14 items-center justify-center rounded-full bg-surface shadow-soft ring-1 ring-border">
                    <span
                        className="ms-0.5 block size-0 border-y-[10px] border-y-transparent border-s-[16px] border-s-purple"
                        aria-hidden
                    />
                </div>
                <p className="text-xs font-medium text-muted">
                    ویدیو معرفی به‌زودی
                </p>
            </div>
        );
    }

    if (mediaState === 'poster') {
        return (
            <div className={shellClass}>
                <img
                    src={HERO_POSTER_SRC}
                    alt="پیش‌نمایش دوره انیماتورشو"
                    className="h-full w-full object-cover"
                    onError={() => setMediaState('placeholder')}
                />
            </div>
        );
    }

    return (
        <div className={shellClass}>
            <video
                className="block h-full w-full border-0 object-cover outline-none"
                autoPlay
                muted
                loop
                playsInline
                poster={HERO_POSTER_SRC}
                onError={() => setMediaState('poster')}
            >
                <source src={HERO_VIDEO_SRC} type="video/mp4" />
            </video>
        </div>
    );
}

function MeetMediaSlot() {
    return (
        <LandingMediaVideo
            videoSrc={MEET_MEDIA.videoSrc}
            posterSrc={MEET_MEDIA.posterSrc}
            ariaLabel="ویدیو معرفی انیماتورشو"
            className="mx-auto w-[290px] shrink-0 rounded-2xl"
            aspectClassName="aspect-square"
        />
    );
}

function MeetAnimatorshoSection() {
    return (
        <section
            id="course-intro"
            className="flex h-[760px] w-full scroll-mt-24 flex-col items-center justify-center gap-6 px-4 pt-[93px] pb-12 text-center"
            aria-labelledby="meet-animatorsho-heading"
        >
            <MeetMediaSlot />

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
                videoSrc={COURSE_OVERVIEW_MEDIA.videoSrc}
                posterSrc={COURSE_OVERVIEW_MEDIA.posterSrc}
                ariaLabel="ویدیو معرفی دوره ساخت انیمیشن"
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
                    videoSrc={NIMVAJABEE_WORLD_MEDIA.videoSrc}
                    posterSrc={NIMVAJABEE_WORLD_MEDIA.posterSrc}
                    ariaLabel="ویدیو دنیای نیم‌وجبی"
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
                videoSrc={AFTER_REGISTRATION_MEDIA.videoSrc}
                posterSrc={AFTER_REGISTRATION_MEDIA.posterSrc}
                ariaLabel="ویدیو بعد از ثبت‌نام"
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
                <div className="mx-auto flex w-full max-w-[390px] flex-col overflow-x-hidden">
                    <MeetAnimatorshoSection />
                    <CourseOverviewSection />
                    <NimvajabeeWorldSection />
                    <CourseChaptersSection />
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
