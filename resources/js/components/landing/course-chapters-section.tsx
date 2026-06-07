import { useState } from 'react';
import { LandingMediaVideo } from '@/components/landing/landing-media-video';
import { cn } from '@/lib/utils';

const LESSONS_INITIAL_COUNT = 7;

type CourseChapter = {
    id: string;
    shortTab: string;
    activeTab: string;
    title: string;
    durationLabel: string;
    updateLabel?: string;
    videoSrc: string;
    posterSrc: string;
    lessons: readonly string[];
};

const COURSE_CHAPTERS: readonly CourseChapter[] = [
    {
        id: 'chapter-1',
        shortTab: 'فصل اول',
        activeTab: 'فتو انیمیشن',
        title: 'شروع ساخت انیمیشن با سیستم',
        durationLabel: '۴ ساعت آموزش',
        videoSrc: '/media/landing/videos/chapter-1.mp4',
        posterSrc: '/media/landing/posters/chapter-1.webp',
        lessons: [
            'تاریخچه انیمیشن سازی',
            'سبک های انیمیشن سازی',
            'سبک نیم وجبی',
            'آشنایی با نیم وجبی',
            'مقدماتی فتوشاپ',
            'انتقال عکس به فتوشاپ',
            'مقدماتی پریمایر',
            'نکاتی برای پریمایر',
            'سناریو نویسی',
            'طراحی و انیمیت',
            'طنازی',
            'تایم لپس انیمیت',
            'خروجی نهایی: تخم مرغ شانسی',
            'طراحی با موس',
            'پشتک زدن',
            'حذف دست و پاگیرها',
            'نکاتی برای پریمایر 2',
        ],
    },
    {
        id: 'chapter-2',
        shortTab: 'فصل دوم',
        activeTab: 'طراحی کاراکتر',
        title: 'طراحی برای انیمیشن',
        durationLabel: '۷ ساعت آموزش',
        videoSrc: '/media/landing/videos/chapter-2.mp4',
        posterSrc: '/media/landing/posters/chapter-2.webp',
        lessons: [
            'مقدمه',
            'مقدماتی فتوشاپ',
            'گرم کردن',
            'کشیدن چشم ها',
            'کشیدن دهان',
            'حالت های مختلف دهان',
            'حالت های مختلف چشم ها',
            'حالت های مختلف صورت',
            'کشیدن گوش ها',
            'کشیدن دماغ',
            'موهای پسرونه',
            'موهای دخترونه',
            'کشیدن حجاب',
            'انواع حجاب',
            'آرت استایل',
            'مفهوم اشکال هندسی',
            'طراحی صورت با اشکال هندسی',
            'تحلیل و طراحی با عکس رفرنس',
            'ترکیب عکس رفرنس با اشکال هندسی',
            'صورت در زوایای مختلف',
            'طراحی دست',
            'طراحی دست 2',
            'طراحی پا',
            'طراحی بدن',
            'طراحی با میوه ها و غذاها',
            'طراحی با حیوانات',
            'طراحی با اشکال ساده',
            'طراحی با اشیاء',
        ],
    },
    {
        id: 'chapter-3',
        shortTab: 'فصل سوم',
        activeTab: 'ادوب انیمیت',
        title: 'ساخت انیمیشن مثل نیم‌وجبی',
        durationLabel: '۱۷ ساعت آموزش',
        videoSrc: '/media/landing/videos/chapter-3.mp4',
        posterSrc: '/media/landing/posters/chapter-3.webp',
        lessons: [
            'آشنایی نصب Adobe Animate',
            'آموزش مقدماتی Adobe Animate',
            'سیمبل ها',
            'بهینه سازی',
            'اصل اول- فشردگی و کشیدگی',
            'اصل دوم- پیش بینی کردن',
            'اصل سوم- صحنه سازی',
            'اصل چهارم- مستقیم و حالت به حالت',
            'اصل پنجم- حرکت های دنباله دار',
            'اصل ششم- افزایش و کاهش سرعت',
            'اصل هفتم- کمان',
            'اصل هشتم- رفتارهای ثانویه',
            'اصل نهم- زمانبندی',
            'اصل دهم- اغراق کردن',
            'اصل یازدهم- طراحی حجم دار',
            'اصل دوازدهم- جذبه',
            'ورکشاپ گنبد آهنین',
            'استوری برد ایده یابی',
            'سناریو',
            'ترسیم آبکش آهنین',
            'ترسیم سیکس پک',
            'ترسیم موشک',
            'ضبط صدا (1)',
            'کیفیت صدا',
            'کاور گنبد آهنین',
            'انیمیت (1)',
            'تدوین (1)',
            'اصلاح سناریو',
            'ضبط صدا (2)',
            'کاور اسرائیل در آماده باش',
            'انیمیت (2)',
            'انیمیت (3)',
            'انیمیت (4)',
            'تدوین (2)',
            'انیمیت (5)',
            'تدوین (3)',
            'انیمیت (6)',
            'انیمیت (7)',
            'تزریق ایده',
            'ضبط صدا (3)',
            'انیمیت (8)',
            'انیمیت (9)',
            'تدوین (4)',
            'صدا افکت',
        ],
    },
    {
        id: 'chapter-4',
        shortTab: 'فصل چهارم',
        activeTab: 'انیمیشن با گوشی',
        title: 'ساخت انیمیشن با گوشی',
        durationLabel: '۸ ساعت آموزش',
        videoSrc: '/media/landing/videos/chapter-4.mp4',
        posterSrc: '/media/landing/posters/chapter-4.webp',
        lessons: [
            'نصب FlipaClip',
            'درست کردن قلم نوری',
            'فضای کار 1',
            'فضای کار 2',
            'تمرین 1',
            'تمرین 2',
            'تمرین 3',
            'تمرین 4',
            'تمرین 5',
            'پیدا کردن ایده',
            'نوشتن سناریو',
            'سرباز',
            'روح',
            'ضبط صدا 1',
            'ظاهر شدن روح',
            'عه صگیونیسم',
            'روحم شاد شد!',
            'ترسوندن 1',
            'ترسوندن 2',
            'ترسوندن 3',
            'ضبط صدا 2',
            'وعده صادق',
            'فرار',
            'خروجی گرفتن',
        ],
    },
    {
        id: 'chapter-5',
        shortTab: 'ورکشاپ 1',
        activeTab: 'دنیای گفتمان ها',
        title: 'ورکشاپ دنیای گفتمان ها',
        durationLabel: '۱ ساعت آموزش',
        updateLabel: 'آپدیت جدید دوره',
        videoSrc: '/media/landing/videos/chapter-5.mp4',
        posterSrc: '/media/landing/posters/chapter-5.webp',
        lessons: [
            'ورکشاپ ونزوئلا قسمت 1',
            'عیب یابی طرح ارسالی',
            'ورکشاپ ونزوئلا قسمت 2',
        ],
    },
    {
        id: 'chapter-6',
        shortTab: 'ورکشاپ 2',
        activeTab: 'تبلیغاتی آموزشی',
        title: 'ورکشاپ 0-100 عربی',
        durationLabel: '۱ ساعت آموزش',
        updateLabel: 'در حال بروزرسانی',
        videoSrc: '/media/landing/videos/chapter-6.mp4',
        posterSrc: '/media/landing/posters/chapter-6.webp',
        lessons: [
            'پیدا کردن ایده - قسمت اول',
            'درحال بروز رسانی',
        ],
    },
] as const;

function ChapterTab({
    chapter,
    isActive,
    onSelect,
}: {
    chapter: CourseChapter;
    isActive: boolean;
    onSelect: () => void;
}) {
    return (
        <button
            type="button"
            role="tab"
            aria-selected={isActive}
            onClick={onSelect}
            className={cn(
                'shrink-0 rounded-pill px-4 py-2 text-sm font-medium transition-colors',
                isActive
                    ? 'bg-text text-white'
                    : 'bg-surface text-muted shadow-none ring-0',
            )}
        >
            {chapter.shortTab}
        </button>
    );
}

function ChapterContentCard({
    chapter,
    expanded,
    onExpand,
}: {
    chapter: CourseChapter;
    expanded: boolean;
    onExpand: () => void;
}) {
    const visibleLessons = expanded
        ? chapter.lessons
        : chapter.lessons.slice(0, LESSONS_INITIAL_COUNT);
    const hasMoreLessons = chapter.lessons.length > LESSONS_INITIAL_COUNT;

    return (
        <div
            role="tabpanel"
            className="mx-auto flex w-[341px] flex-col justify-center gap-5 rounded-[28px] bg-[#e9e7e5] p-4"
        >
            <LandingMediaVideo
                key={chapter.id}
                videoSrc={chapter.videoSrc}
                posterSrc={chapter.posterSrc}
                ariaLabel={`ویدیو ${chapter.title}`}
                aspectClassName="h-[240px]"
                className="rounded-2xl"
                enabled
            />

            <div className="flex flex-col items-start gap-3 text-right">
                <h3 className="text-base font-bold text-text">{chapter.title}</h3>

                <div className="flex flex-wrap items-center gap-2">
                    <span className="rounded-pill bg-purple-soft px-2.5 py-1 text-xs font-medium text-purple">
                        {chapter.durationLabel}
                    </span>
                    {chapter.updateLabel ? (
                        <span className="rounded-pill bg-green-soft px-2.5 py-1 text-xs font-medium text-green">
                            {chapter.updateLabel}
                        </span>
                    ) : null}
                </div>

                <ul className="flex w-full flex-col gap-2.5">
                    {visibleLessons.map((lesson) => (
                        <li
                            key={lesson}
                            className="text-sm leading-relaxed text-text"
                        >
                            {lesson}
                        </li>
                    ))}
                </ul>

                {hasMoreLessons && !expanded ? (
                    <button
                        type="button"
                        onClick={onExpand}
                        className="inline-flex items-baseline gap-1 text-sm text-muted transition-colors hover:text-text"
                    >
                        <span className="border-b border-dotted border-current pb-0.5">
                            مشاهده بیشتر
                        </span>
                        <span aria-hidden="true">...</span>
                    </button>
                ) : null}
            </div>
        </div>
    );
}

export function CourseChaptersSection() {
    const [activeChapterId, setActiveChapterId] = useState(
        COURSE_CHAPTERS[0].id,
    );
    const [expanded, setExpanded] = useState(false);

    const activeChapter =
        COURSE_CHAPTERS.find((chapter) => chapter.id === activeChapterId) ??
        COURSE_CHAPTERS[0];

    function selectChapter(chapterId: string) {
        setActiveChapterId(chapterId);
        setExpanded(false);
    }

    return (
        <section
            id="course-chapters"
            className="flex w-full scroll-mt-24 flex-col items-center gap-6 px-4 py-[62px] text-center"
            aria-labelledby="course-chapters-heading"
        >
            <h2
                id="course-chapters-heading"
                className="font-display pb-[15px] text-[1.75rem] leading-tight font-bold text-text"
            >
                <span className="block">با انیماتورشو</span>
                <span className="mt-1 block">چی یاد می‌گیری؟</span>
            </h2>

            <div className="w-full min-w-0">
                <div
                    role="tablist"
                    aria-label="سرفصل‌های دوره"
                    className="-mx-4 flex min-w-0 flex-nowrap gap-2 overflow-x-auto overscroll-x-contain px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    {COURSE_CHAPTERS.map((chapter) => (
                        <ChapterTab
                            key={chapter.id}
                            chapter={chapter}
                            isActive={chapter.id === activeChapterId}
                            onSelect={() => selectChapter(chapter.id)}
                        />
                    ))}
                </div>
            </div>

            <ChapterContentCard
                chapter={activeChapter}
                expanded={expanded}
                onExpand={() => setExpanded(true)}
            />
        </section>
    );
}
