/**
 * Landing page media slot definitions.
 * Final photos/videos are deferred pre-launch — paths are targets under `public/`.
 * UI components degrade to poster → placeholder when files are absent.
 */

export type LandingVideoSlot = {
    videoSrc: string;
    posterSrc: string;
    ariaLabel: string;
};

export type LandingImageSlot = {
    src: string;
    ariaLabel: string;
};

export type StudentWorkMediaSlot = {
    id: string;
    studentName: string;
    badgeLabel: string;
    projectTitle: string;
    videoSrc: string;
    posterSrc: string;
    avatarSrc: string;
};

export type CourseChapterMediaSlot = {
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

export const LANDING_HERO_MEDIA = {
    videoSrc: '/media/landing/hero-video.mp4',
    posterSrc: '/media/landing/hero-poster.webp',
    ariaLabel: 'پیش‌نمایش ویدیو معرفی دوره',
} as const satisfies LandingVideoSlot;

export const LANDING_MEET_MEDIA = {
    videoSrc: '/media/landing/videos/meet-intro.mp4',
    posterSrc: '/media/landing/posters/meet-intro.webp',
    ariaLabel: 'ویدیو معرفی انیماتورشو',
} as const satisfies LandingVideoSlot;

export const LANDING_COURSE_OVERVIEW_MEDIA = {
    videoSrc: '/media/landing/videos/course-intro.mp4',
    posterSrc: '/media/landing/posters/course-intro.webp',
    ariaLabel: 'ویدیو معرفی دوره ساخت انیمیشن',
} as const satisfies LandingVideoSlot;

export const LANDING_NIMVAJABEE_WORLD_MEDIA = {
    videoSrc: '/media/landing/videos/nimvajabee-world.mp4',
    posterSrc: '/media/landing/posters/nimvajabee-world.webp',
    ariaLabel: 'ویدیو دنیای نیم‌وجبی',
} as const satisfies LandingVideoSlot;

export const LANDING_AFTER_REGISTRATION_MEDIA = {
    videoSrc: '/media/landing/videos/after-registration.mp4',
    posterSrc: '/media/landing/posters/after-registration.webp',
    ariaLabel: 'ویدیو بعد از ثبت‌نام',
} as const satisfies LandingVideoSlot;

export const LANDING_FAQ_IMAGE = {
    src: '/media/landing/posters/faq-section.webp',
    ariaLabel: 'تصویر بخش سوالات پرتکرار',
} as const satisfies LandingImageSlot;

export const LANDING_CONSULTATION_IMAGE = {
    src: '/media/landing/posters/consultation-section.webp',
    ariaLabel: 'تصویر بخش مشاوره رایگان',
} as const satisfies LandingImageSlot;

export const LANDING_FINAL_CTA_IMAGE = {
    src: '/media/landing/posters/final-cta-section.webp',
    ariaLabel: 'پس‌زمینه بخش دعوت به ثبت‌نام',
} as const satisfies LandingImageSlot;

export const LANDING_PURCHASE_KEY_IMAGE = {
    src: '/media/landing/posters/purchase-section-key.webp',
    ariaLabel: 'تصویر بخش خرید',
} as const satisfies LandingImageSlot;

export const LANDING_STUDENT_WORKS = [
    {
        id: 'student-1',
        studentName: 'علی رضایی',
        badgeLabel: 'هنرجوی انیماتورشو',
        projectTitle: 'اولین انیمیشن کوتاه',
        videoSrc: '/media/landing/student-works/videos/student-1.mp4',
        posterSrc: '/media/landing/student-works/posters/student-1.webp',
        avatarSrc: '/media/landing/student-works/avatars/student-1.webp',
    },
    {
        id: 'student-2',
        studentName: 'نرگس محمدی',
        badgeLabel: 'هنرجوی انیماتورشو',
        projectTitle: 'تمرین حرکت کاراکتر',
        videoSrc: '/media/landing/student-works/videos/student-2.mp4',
        posterSrc: '/media/landing/student-works/posters/student-2.webp',
        avatarSrc: '/media/landing/student-works/avatars/student-2.webp',
    },
    {
        id: 'student-3',
        studentName: 'امیرحسین کریمی',
        badgeLabel: 'هنرجوی انیماتورشو',
        projectTitle: 'تمرین صداگذاری',
        videoSrc: '/media/landing/student-works/videos/student-3.mp4',
        posterSrc: '/media/landing/student-works/posters/student-3.webp',
        avatarSrc: '/media/landing/student-works/avatars/student-3.webp',
    },
] as const satisfies readonly StudentWorkMediaSlot[];

export const LANDING_COURSE_CHAPTERS = [
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
] as const satisfies readonly CourseChapterMediaSlot[];
