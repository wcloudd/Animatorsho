# مستندات فایل‌های رسانه‌ای سایت انیماتورشو

این فایل راهنمای طراح/مونتاژگر برای تمام تصاویر و ویدیوهای مورد نیاز سایت است.
فایل‌های رمزی (code names)، کامپوننت‌های مربوطه، و spec خروجی در اینجا مشخص شده‌اند.

**آخرین بررسی:** ۲۰۲۶-۰۶-۲۸  
**فایل‌های رجوع در کد:** `resources/js/lib/landing-media.ts` · `resources/js/lib/brand-assets.ts` · `config/student_panel.php`

---

## ۱. خلاصه سریع

### پوشه‌های اصلی رسانه

```
public/
├── favicon.ico                          ← فاوآیکن مرورگر
├── favicon.svg                          ← فاوآیکن مرورگرهای مدرن
├── apple-touch-icon.png                 ← آیکن iOS
├── images/
│   ├── animatorsho-logo.svg             ← لوگوی اصلی (wordmark)
│   ├── animatorsho-logo-active.svg      ← لوگوی حالت فعال (bottom-nav)
│   ├── brand/                           ← آینده: لوگو‌های کامپکت
│   └── seo/
│       └── animatorsho-og.webp          ← تصویر OG / شبکه‌های اجتماعی (هنوز آپلود نشده)
└── media/
    └── landing/
        ├── hero-video.mp4               ← ویدیوی پیش‌نمایش hero
        ├── hero-video-click.mp4         ← ویدیوی کامل معرفی دوره (مدال پلیر)
        ├── hero-poster.webp             ← پوستر hero (هنوز آپلود نشده)
        ├── videos/                      ← ویدیوهای بخش‌های صفحه اصلی
        ├── posters/                     ← پوسترها و تصاویر ثابت بخش‌ها
        └── student-works/
            ├── videos/                  ← ویدیوهای نمونه کار هنرجویان
            ├── posters/                 ← تصاویر بندانگشتی نمونه‌کارها
            └── avatars/                 ← آواتار هنرجویان
    └── student-panel/                   ← تصاویر داشبورد هنرجو
```

### قوانین نام‌گذاری

- **نام فایل‌ها را تغییر ندهید** — کد به مسیر ثابت وابسته است.
- اگر نیاز به تغییر نام دارید، مسیر را در `landing-media.ts` یا `brand-assets.ts` نیز تغییر دهید.
- از حروف کوچک انگلیسی و خط تیره (`-`) استفاده کنید.
- پسوند `.webp` برای تصاویر، `.mp4` برای ویدیو ترجیح داده می‌شود.

### قوانین جایگزینی

۱. فایل جدید را با **همان نام** در **همان مسیر** کپی کنید.
۲. `npm run build` اجرا کنید — خطا نباید باشد.
۳. صفحه اصلی را در عرض موبایل (390px) و دسکتاپ تست کنید.
۴. اگر ویدیوی hero را تغییر دادید، کلیک روی آن و باز شدن مودال را هم تست کنید.

---

## ۲. جدول فایل‌های ویدئویی

### ۲.۱ ویدیوهای اصلی صفحه hero

| نام فایل | مسیر کامل | وضعیت | نقش در سایت | کامپوننت | نسبت تصویر | ابعاد پیشنهادی خروجی | فرمت | نکات مهم |
|---|---|---|---|---|---|---|---|---|
| `hero-video.mp4` | `public/media/landing/hero-video.mp4` | ✅ موجود | ویدیوی پیش‌نمایش hero — همیشه روی صفحه اصلی پخش می‌شود | `HeroMediaCard` در `pages/animatorsho/index.tsx` | **16:9** (`aspect-video`) | 780×439 px | MP4 H.264 | `autoPlay`, `muted`, `loop`, `playsInline` — بدون کنترل — کلیک روی آن مودال را باز می‌کند |
| `hero-video-click.mp4` | `public/media/landing/hero-video-click.mp4` | ✅ موجود | ویدیوی کامل معرفی دوره — فقط در مودال پخش می‌شود | `HeroMediaCard` در `pages/animatorsho/index.tsx` | پیشنهادی: **16:9** | 1280×720 یا 1920×1080 | MP4 H.264 | `controls`, `autoPlay`, `playsInline` — در مودال lightbox — صدا دارد — بدون مقید کردن به ابعاد container |
| `hero-poster.webp` | `public/media/landing/hero-poster.webp` | ❌ آپلود نشده | پوستر پشتیبان ویدیوی hero — وقتی ویدیو لود نشد نمایش داده می‌شود | `LandingMediaVideo` (prop: `posterSrc`) | **16:9** | 780×439 px | WebP | fallback تصویر — اگر نباشد placeholder نمایش داده می‌شود |

> **نکته:** فایل `public/media/landing/19FPS.mp4` در پوشه وجود دارد اما در هیچ کجای کد استفاده نشده. احتمالاً فایل آزمایشی است — قبل از استقرار production بررسی و در صورت لزوم حذف کنید.

---

### ۲.۲ ویدیوهای بخش‌های صفحه اصلی (حلقه‌ای — بدون صدا)

همه این ویدیوها با `LandingMediaVideo` نمایش داده می‌شوند: `autoPlay`, `muted`, `loop`, `playsInline`, `preload="none"`, فعال‌سازی lazy هنگام ورود به viewport.

| نام فایل | مسیر کامل | وضعیت | بخش نمایش | ابعاد نمایش در سایت | نسبت تصویر | ابعاد پیشنهادی خروجی | نکات |
|---|---|---|---|---|---|---|---|
| `meet-intro.mp4` | `public/media/landing/videos/meet-intro.mp4` | ✅ موجود | «با انیماتورشو بیشتر آشناشو» | 290×290 px | **1:1 مربع** | 580×580 px | `aspect-square`, `w-[290px]` — کرپ مربعی |
| `course-intro.mp4` | `public/media/landing/videos/course-intro.mp4` | ❌ آپلود نشده | «ساخت انیمیشن از ایده تا اولین خروجی» | عرض کامل در 390px، نسبت مربع | **1:1 مربع** | 780×780 px | `aspect-square` — کرپ مربعی |
| `nimvajabee-world.mp4` | `public/media/landing/videos/nimvajabee-world.mp4` | ❌ آپلود نشده | «یادگیری از خالق نیم‌وجبی» | عرض کامل در 390px، نسبت مربع | **1:1 مربع** | 780×780 px | `aspect-square` — دارای دکمه CTA روی ویدیو |
| `after-registration.mp4` | `public/media/landing/videos/after-registration.mp4` | ❌ آپلود نشده | «بعد از ثبت‌نام چه اتفاقی می‌افته؟» | عرض کامل در 390px، نسبت مربع | **1:1 مربع** | 780×780 px | `aspect-square` |

---

### ۲.۳ ویدیوهای پیش‌نمایش فصل‌های دوره (تب‌های course chapters)

نمایش در کارت با عرض `341px` — ارتفاع خودکار (`h-auto`) بر اساس نسبت تصویر فایل — بدون کرپ (`object-cover` حذف شده).

| نام فایل | مسیر کامل | وضعیت | فصل | ابعاد پیشنهادی خروجی |
|---|---|---|---|---|
| `chapter-1.mp4` | `public/media/landing/videos/chapter-1.mp4` | ❌ آپلود نشده | فتو انیمیشن (فصل اول) | 682×480 px (نسبت 341:240 ≈ 17:12) |
| `chapter-2.mp4` | `public/media/landing/videos/chapter-2.mp4` | ❌ آپلود نشده | طراحی کاراکتر (فصل دوم) | 682×480 px |
| `chapter-3.mp4` | `public/media/landing/videos/chapter-3.mp4` | ❌ آپلود نشده | ادوب انیمیت (فصل سوم) | 682×480 px |
| `chapter-4.mp4` | `public/media/landing/videos/chapter-4.mp4` | ❌ آپلود نشده | انیمیشن با گوشی (فصل چهارم) | 682×480 px |
| `chapter-5.mp4` | `public/media/landing/videos/chapter-5.mp4` | ❌ آپلود نشده | ورکشاپ ترن هوایی (ورکشاپ ۱) | هر نسبتی — ارتفاع خودکار |
| `chapter-6.mp4` | `public/media/landing/videos/chapter-6.mp4` | ❌ آپلود نشده | ورکشاپ پرتقال (ورکشاپ ۲) | هر نسبتی — ارتفاع خودکار |
| `chapter-7.mp4` | `public/media/landing/videos/chapter-7.mp4` | ❌ آپلود نشده | ورکشاپ مگس (ورکشاپ ۳) | هر نسبتی — ارتفاع خودکار |

> **نکته:** چون کارت‌های فصل اکنون با `h-auto` نمایش داده می‌شوند (بدون `object-cover`)، ویدیو/تصویر در نسبت اصلی خودش نمایش داده می‌شود. هر نسبتی (16:9، 4:3، 1:1) قابل قبول است — ارتفاع کارت با محتوا تنظیم می‌شود.

---

### ۲.۴ ویدیوهای بخش مدرس دوره

| نام فایل | مسیر کامل | وضعیت | نقش در سایت | کامپوننت | نسبت تصویر | ابعاد پیشنهادی خروجی | نکات |
|---|---|---|---|---|---|---|---|
| `teacher-portfolio-preview.mp4` | `public/media/landing/videos/teacher-portfolio-preview.mp4` | ❌ آپلود نشده | پیش‌نمایش loop نمونه‌کارهای مدرس در بخش «آشنایی با مدرس دوره» | `InstructorSection` در `pages/animatorsho/index.tsx` | **1:1 مربع** | 780×780 px | `autoPlay`, `muted`, `loop`, `playsInline` — کلیک روی آن مودال را باز می‌کند |
| `teacher-portfolio-click.mp4` | `public/media/landing/videos/teacher-portfolio-click.mp4` | ❌ آپلود نشده | ویدیوی کامل نمونه‌کارها — فقط در مودال پخش می‌شود | `LandingVideoModal` (از `InstructorSection`) | پیشنهادی: **16:9** | 1280×720 یا 1920×1080 | `controls`, `autoPlay`, `playsInline` — در مودال lightbox — صدا دارد |
| `ai-animation-preview.mp4` | `public/media/landing/videos/ai-animation-preview.mp4` | ❌ آپلود نشده | ویدیوی loop بخش «هوش مصنوعی خوبه یا بد؟» | `AiAnimationSection` در `pages/animatorsho/index.tsx` | **1:1 مربع** | 780×780 px | `autoPlay`, `muted`, `loop`, `playsInline` — بدون کنترل — بدون مودال |

---

### ۲.۵ ویدیوهای بخش دوره حضوری

| نام فایل | مسیر کامل | وضعیت | نقش در سایت | کامپوننت | نسبت تصویر | ابعاد پیشنهادی خروجی | نکات |
|---|---|---|---|---|---|---|---|
| `in-person-course-preview.mp4` | `public/media/landing/videos/in-person-course-preview.mp4` | ❌ آپلود نشده | پیش‌نمایش loop گزارش دوره حضوری — بخش «دوره حضوری» | `InPersonCourseSection` در `pages/animatorsho/index.tsx` | **16:9** | 1280×720 px | `autoPlay`, `muted`, `loop`, `playsInline` — بدون کنترل — کلیک روی آن مودال را باز می‌کند |
| `in-person-course-click.mp4` | `public/media/landing/videos/in-person-course-click.mp4` | ❌ آپلود نشده | ویدیوی کامل گزارش دوره حضوری — فقط در مودال پخش می‌شود | `LandingVideoModal` (از `InPersonCourseSection`) | پیشنهادی: **16:9** | 1280×720 یا 1920×1080 | `controls`, `autoPlay`, `playsInline` — در مودال lightbox — صدا دارد |

---

### ۲.۶ ویدیوهای نمونه کار هنرجویان (carousel + مودال)

در کارت‌های carousel با بند انگشتی مربعی (280×280 px) نمایش داده می‌شوند. با کلیک، مودال با کنترل‌های native باز می‌شود.

| نام فایل | مسیر کامل | وضعیت | بندانگشتی: نسبت / ابعاد | مودال: پیشنهاد ابعاد |
|---|---|---|---|---|
| `student-1.mp4` | `public/media/landing/student-works/videos/student-1.mp4` | ❌ آپلود نشده | 1:1 مربع — 280×280 px display | 1:1 یا 16:9 — حداقل 560×560 px |
| `student-2.mp4` | `public/media/landing/student-works/videos/student-2.mp4` | ❌ آپلود نشده | 1:1 مربع | همان |
| `student-3.mp4` | `public/media/landing/student-works/videos/student-3.mp4` | ❌ آپلود نشده | 1:1 مربع | همان |

---

### ۲.۷ ویدیوی پنل هنرجو

| نام فایل | مسیر کامل | وضعیت | نقش | نکات |
|---|---|---|---|---|
| `start-guide.mp4` | `public/media/student-panel/start-guide.mp4` | ❌ آپلود نشده | ویدئوی راهنمای شروع در پنل هنرجو | در مودال با `controls` پخش می‌شود — دارای صدا |

---

## ۳. جدول فایل‌های تصویری صفحه اصلی / Landing

### ۳.۱ تصویر مدرس دوره

| نام فایل | مسیر کامل | وضعیت | نقش | کامپوننت | ابعاد نمایش | ابعاد پیشنهادی | فرمت | نکات |
|---|---|---|---|---|---|---|---|---|
| `teacher-photo.webp` | `public/media/landing/posters/teacher-photo.webp` | ❌ آپلود نشده | عکس ابوالفضل رستگارمقدم در بخش «آشنایی با مدرس دوره» | `InstructorSection` در `pages/animatorsho/index.tsx` | 200×200 px (مربع) | 400×400 px | WebP | `object-cover`, مربع — اگر نباشد placeholder نمایش داده می‌شود |
| `teacher-portfolio-preview.webp` | `public/media/landing/posters/teacher-portfolio-preview.webp` | ❌ آپلود نشده | پوستر پشتیبان ویدیوی loop نمونه‌کارها | `LandingMediaVideo` (prop: `posterSrc`) | عرض کامل، مربع | 780×780 px | WebP | fallback هنگام عدم لود ویدیو |
| `ai-animation-preview.webp` | `public/media/landing/posters/ai-animation-preview.webp` | ❌ آپلود نشده | پوستر پشتیبان ویدیوی بخش هوش مصنوعی | `LandingMediaVideo` (prop: `posterSrc`) | عرض کامل، مربع | 780×780 px | WebP | fallback هنگام عدم لود ویدیو |

---

### ۳.۳ پوسترهای ویدیوها (fallback هنگام عدم لود ویدیو)

| نام فایل | مسیر کامل | وضعیت | ویدیوی متناظر | ابعاد پیشنهادی | نسبت | فرمت |
|---|---|---|---|---|---|---|
| `hero-poster.webp` | `public/media/landing/hero-poster.webp` | ❌ آپلود نشده | `hero-video.mp4` | 780×439 px (پیشنهادی) | 16:9 | WebP |
| `meet-intro.webp` | `public/media/landing/posters/meet-intro.webp` | ❌ آپلود نشده | `meet-intro.mp4` | 580×580 px (پیشنهادی) | 1:1 | WebP |
| `course-intro.webp` | `public/media/landing/posters/course-intro.webp` | ❌ آپلود نشده | `course-intro.mp4` | 780×780 px (پیشنهادی) | 1:1 | WebP |
| `nimvajabee-world.webp` | `public/media/landing/posters/nimvajabee-world.webp` | ❌ آپلود نشده | `nimvajabee-world.mp4` | 780×780 px (پیشنهادی) | 1:1 | WebP |
| `after-registration.webp` | `public/media/landing/posters/after-registration.webp` | ❌ آپلود نشده | `after-registration.mp4` | 780×780 px (پیشنهادی) | 1:1 | WebP |
| `in-person-course-preview.webp` | `public/media/landing/posters/in-person-course-preview.webp` | ❌ آپلود نشده | `in-person-course-preview.mp4` | 780×439 px (پیشنهادی) | 16:9 | WebP |
| `chapter-1.webp` | `public/media/landing/posters/chapter-1.webp` | ❌ آپلود نشده | `chapter-1.mp4` | 682×480 px (پیشنهادی) | ≈17:12 | WebP |
| `chapter-2.webp` | `public/media/landing/posters/chapter-2.webp` | ❌ آپلود نشده | `chapter-2.mp4` | 682×480 px (پیشنهادی) | ≈17:12 | WebP |
| `chapter-3.webp` | `public/media/landing/posters/chapter-3.webp` | ❌ آپلود نشده | `chapter-3.mp4` | 682×480 px (پیشنهادی) | ≈17:12 | WebP |
| `chapter-4.webp` | `public/media/landing/posters/chapter-4.webp` | ❌ آپلود نشده | `chapter-4.mp4` | 682×480 px (پیشنهادی) | ≈17:12 | WebP |
| `chapter-5.webp` | `public/media/landing/posters/chapter-5.webp` | ❌ آپلود نشده | `chapter-5.mp4` (ورکشاپ ترن هوایی) | هر نسبتی — ارتفاع خودکار | آزاد | WebP |
| `chapter-6.webp` | `public/media/landing/posters/chapter-6.webp` | ❌ آپلود نشده | `chapter-6.mp4` (ورکشاپ پرتقال) | هر نسبتی — ارتفاع خودکار | آزاد | WebP |
| `chapter-7.webp` | `public/media/landing/posters/chapter-7.webp` | ❌ آپلود نشده | `chapter-7.mp4` (ورکشاپ مگس) | هر نسبتی — ارتفاع خودکار | آزاد | WebP |

---

### ۳.۴ تصاویر ثابت بخش‌های landing (static illustrations)

کامپوننت: `LandingMediaImage` — اگر فایل نباشد placeholder نمایش داده می‌شود.

| نام فایل | مسیر کامل | وضعیت | بخش استفاده | کامپوننت | CSS container | ابعاد پیشنهادی | نسبت | فرمت | جایگزینی امن؟ |
|---|---|---|---|---|---|---|---|---|---|
| `faq-section.webp` | `public/media/landing/posters/faq-section.webp` | ❌ آپلود نشده | بخش «سوالات پرتکرار» | `FaqSection` | `aspect-[4/3] w-full` | 780×585 px (پیشنهادی) | 4:3 | WebP | ✅ بله — فقط فایل را جایگزین کنید |
| `consultation-section.webp` | `public/media/landing/posters/consultation-section.webp` | ❌ آپلود نشده | بخش «مشاوره رایگان» | `ConsultationCtaSection` | `aspect-[4/3] w-full` | 780×585 px (پیشنهادی) | 4:3 | WebP | ✅ بله |
| `final-cta-section.webp` | `public/media/landing/posters/final-cta-section.webp` | ❌ آپلود نشده | بخش دعوت به ثبت‌نام (انتهای صفحه) | `FinalCtaSection` | `absolute inset-0` — full bleed background | 780×828 px (پیشنهادی — ارتفاع container: min 828px) | پیشنهادی: 9:10 | WebP | ✅ بله — `object-cover` |
| `purchase-section-key.webp` | `public/media/landing/posters/purchase-section-key.webp` | ❌ آپلود نشده | بخش خرید — تصویر decorative | `PurchaseSectionIllustration` | `max-h-[200px] max-w-[220px] object-contain` | حداکثر 440×400 px | آزاد — `object-contain` | WebP یا PNG (در صورت نیاز به شفافیت) | ✅ بله |

---

### ۳.۵ نمونه کار هنرجویان — پوستر و آواتار

| نام فایل | مسیر کامل | وضعیت | نقش | ابعاد نمایش | ابعاد پیشنهادی | فرمت |
|---|---|---|---|---|---|---|
| `student-1.webp` | `public/media/landing/student-works/posters/student-1.webp` | ❌ آپلود نشده | بندانگشتی ویدیوی هنرجو ۱ | 280×280 px (مربع) | 560×560 px (پیشنهادی) | WebP |
| `student-2.webp` | `public/media/landing/student-works/posters/student-2.webp` | ❌ آپلود نشده | بندانگشتی ویدیوی هنرجو ۲ | 280×280 px | 560×560 px (پیشنهادی) | WebP |
| `student-3.webp` | `public/media/landing/student-works/posters/student-3.webp` | ❌ آپلود نشده | بندانگشتی ویدیوی هنرجو ۳ | 280×280 px | 560×560 px (پیشنهادی) | WebP |
| `student-1.webp` | `public/media/landing/student-works/avatars/student-1.webp` | ❌ آپلود نشده | آواتار هنرجو ۱ در کارت | دایره کوچک (Radix Avatar) | 128×128 px (پیشنهادی) | WebP |
| `student-2.webp` | `public/media/landing/student-works/avatars/student-2.webp` | ❌ آپلود نشده | آواتار هنرجو ۲ | همان | 128×128 px (پیشنهادی) | WebP |
| `student-3.webp` | `public/media/landing/student-works/avatars/student-3.webp` | ❌ آپلود نشده | آواتار هنرجو ۳ | همان | 128×128 px (پیشنهادی) | WebP |

> **توجه:** اگر آواتار آپلود نشود، حرف اول نام هنرجو به صورت fallback نمایش داده می‌شود — رفتار امن است.

---

## ۴. جدول تصاویر پنل هنرجو / Course Panel

همه فایل‌ها در مسیر `public/media/student-panel/` قرار می‌گیرند. وجود فایل توسط سرور به صورت خودکار تشخیص داده می‌شود — نیازی به ویرایش config نیست. منبع: `config/student_panel.php` + `app/Support/StudentPanel/StudentPanelMedia.php`.

### ۴.۱ تصاویر header کارت‌های داشبورد

| نام فایل | مسیر کامل | وضعیت | نقش — کارت مربوطه | ابعاد تخمینی در UI | ابعاد پیشنهادی خروجی | فرمت |
|---|---|---|---|---|---|---|
| `exercises-header.png` | `public/media/student-panel/exercises-header.png` | ✅ موجود | تصویر header کارت «تمرین‌های من» | نیاز به بررسی دستی | نیاز به بررسی دستی | PNG |
| `onboarding-banner.png` | `public/media/student-panel/onboarding-banner.png` | ❌ آپلود نشده | تصویر banner کارت onboarding (راهنمای شروع) | نیاز به بررسی دستی | نیاز به بررسی دستی | PNG |
| `mentor-header.png` | `public/media/student-panel/mentor-header.png` | ❌ آپلود نشده | تصویر header کارت «گفتگو با استاد» | نیاز به بررسی دستی | نیاز به بررسی دستی | PNG |
| `resources-header.png` | `public/media/student-panel/resources-header.png` | ❌ آپلود نشده | تصویر header کارت «کتابخانه تمرین» | نیاز به بررسی دستی | نیاز به بررسی دستی | PNG |
| `medals-header.png` | `public/media/student-panel/medals-header.png` | ❌ آپلود نشده | تصویر header کارت «مدال‌ها» | نیاز به بررسی دستی | نیاز به بررسی دستی | PNG |
| `updates-header.png` | `public/media/student-panel/updates-header.png` | ❌ آپلود نشده | تصویر header کارت «آخرین آپدیت‌ها» | نیاز به بررسی دستی | نیاز به بررسی دستی | PNG |

> **برای تعیین ابعاد دقیق:** صفحه `/course` را در مرورگر باز کنید، روی placeholder هر کارت inspect کنید و ابعاد render شده را بخوانید. سپس در این جدول وارد کنید.

### ۴.۲ فایل‌های راهنما (start guide)

| نام فایل | مسیر کامل | وضعیت | نقش |
|---|---|---|---|
| `start-guide-poster.png` | `public/media/student-panel/start-guide-poster.png` | ❌ آپلود نشده | تصویر پوستر ویدیوی راهنما در مودال — قبل از پخش ویدیو نمایش داده می‌شود |
| `start-guide.pdf` | `public/media/student-panel/start-guide.pdf` | ❌ آپلود نشده | فایل PDF راهنمای شروع — دکمه «دانلود راهنما» |

### ۴.۳ منابع آپلود‌شده توسط ادمین (dynamic)

| نام فایل | مسیر کامل | وضعیت | نقش |
|---|---|---|---|
| `resources/a11.png` | `public/media/student-panel/resources/a11.png` | ✅ موجود (منشا نامشخص) | احتمالاً resource آپلودشده توسط ادمین — در کد مستقیم ارجاع داده نشده |

> **توجه:** فایل `a11.png` به نظر می‌رسد توسط ادمین آپلود شده و یک منبع دوره است — نه یک asset ثابت طراحی. قبل از حذف با تیم بررسی کنید.

---

## ۵. جدول تصاویر برند، لوگو، آیکون‌ها و SEO

| نام فایل | مسیر کامل | وضعیت | نقش | ابعاد / فرمت | محل استفاده در کد |
|---|---|---|---|---|---|
| `animatorsho-logo.svg` | `public/images/animatorsho-logo.svg` | ✅ موجود | لوگوی اصلی wordmark — حالت غیرفعال | SVG — عرض متغیر | `BRAND_LOGO_SRC` — bottom-nav, auth layout |
| `animatorsho-logo-active.svg` | `public/images/animatorsho-logo-active.svg` | ✅ موجود | لوگوی wordmark — حالت active (رنگ متفاوت) | SVG — عرض متغیر | `BRAND_LOGO_ACTIVE_SRC` — bottom-nav حالت فعال |
| `animatorsho-mark.svg` | `public/images/brand/animatorsho-mark.svg` | ❌ آپلود نشده | نشان کمپکت برند (mark/icon) — آینده | SVG — مربع | `BRAND_LOGO_MARK_SRC` — reserved برای header و UI کوچک |
| `animatorsho-logo.svg` | `public/images/brand/animatorsho-logo.svg` | ❌ آپلود نشده | لوگوی کامل — مسیر migration آینده | SVG | `BRAND_LOGO_TARGET_SRC` — deferred |
| `favicon.ico` | `public/favicon.ico` | ✅ موجود | فاوآیکن مرورگر (تمام سایزها) | ICO — 16×16, 32×32, 48×48 | `resources/views/app.blade.php` |
| `favicon.svg` | `public/favicon.svg` | ✅ موجود | فاوآیکن SVG — مرورگرهای مدرن | SVG — مربع | `resources/views/app.blade.php` |
| `apple-touch-icon.png` | `public/apple-touch-icon.png` | ✅ موجود | آیکن iOS home screen | **180×180 px** PNG | `resources/views/app.blade.php` |
| `animatorsho-og.webp` | `public/images/seo/animatorsho-og.webp` | ❌ آپلود نشده | تصویر OG — شبکه‌های اجتماعی، اشتراک‌گذاری | **1200×630 px** WebP | `BRAND_OG_IMAGE_PATH` — `SeoHead` component |

> **درباره OG image:** تا زمانی که `animatorsho-og.webp` آپلود نشود، سیستم از `BRAND_OG_IMAGE_FALLBACK_PATH` (لوگوی SVG) استفاده می‌کند. بعد از آپلود، مقدار `default_og_image` در `config/seo.php` را نیز به‌روز کنید.

> **درباره فونت‌ها:** فونت‌های IRANYekanX و Liana در `public/fonts/` قرار دارند و از طریق CSS (`@font-face`) بارگذاری می‌شوند — نیازی به مستندسازی جداگانه ندارند مگر برای تیم typography.

---

## ۶. قوانین طراحی و خروجی گرفتن فایل‌ها

### ویدیو
- **codec:** H.264 (AVC) — سازگاری بالا با همه مرورگرها
- **container:** MP4
- **ویدیوهای بدون صدا:** track صوتی را حذف کنید (ویدیوهای loop صفحه اصلی)
- **ویدیوهای با صدا:** (`hero-video-click.mp4`, `start-guide.mp4`) — میکس مناسب، normalize صدا
- **کمپرس:** `-movflags +faststart` برای شروع سریع در مرورگر — حتماً اجرا کنید
- **حجم ویدیوهای loop:** هدف ≤2 MB هر کدام (5–20 ثانیه)
- **حجم ویدیوهای طولانی:** (`hero-video-click.mp4`) بر اساس طول — bitrate پیشنهادی 1–3 Mbps
- **fps:** 24–30 فریم در ثانیه

### تصویر — WebP
- **کیفیت:** 75–85 برای عکس‌های واقعی، 90+ برای تصاویر UI/گرافیکی
- **رنگ:** sRGB
- **آلفا:** فقط در صورت نیاز به شفافیت
- **ابعاد:** ×2 اندازه display برای صفحه‌های Retina

### تصویر — PNG
- تصاویر با شفافیت یا گرافیک تخت
- برای header های پنل هنرجو که نیاز به شفافیت دارند مناسب است

### SVG
- با SVGO optimize کنید
- لوگوها را به صورت single-color یا gradient ساده نگه دارید
- `viewBox` تنظیم باشد تا در هر سایزی خوب رندر شود

### OG Image
- متن را در 900×472 px مرکزی نگه دارید (safe zone)
- از متن خیلی کوچک خودداری کنید — در mobile crop می‌شود
- تست در opengraph.xyz یا debuggers شبکه‌های اجتماعی

### نکات موبایل
- صفحه اصلی برای viewport عرض 390px طراحی شده
- همه تصاویر و ویدیوها باید در این عرض درست نمایش داده شوند
- پوسترها حتماً قبل از لود ویدیو باید قابل نمایش باشند

---

## ۷. چک‌لیست تعویض فایل

قبل از هر جایگزینی این موارد را مرور کنید:

- [ ] نام فایل با آنچه در جداول بالا آمده **دقیقاً یکسان** است
- [ ] فایل در **همان مسیر** کپی شده (پوشه صحیح)
- [ ] فرمت فایل صحیح است (mp4 / webp / png / svg)
- [ ] `npm run build` اجرا شد و خطا ندارد
- [ ] صفحه اصلی در عرض موبایل (390px) تست شد
- [ ] صفحه اصلی در عرض دسکتاپ تست شد
- [ ] اگر `hero-video.mp4` تغییر کرد: کلیک روی آن را تست کنید و مطمئن شوید مودال با `hero-video-click.mp4` باز می‌شود
- [ ] اگر `hero-video-click.mp4` تغییر کرد: کنترل‌های native ویدیو (play/pause/seek/volume) نمایش دارند
- [ ] اگر تصویر OG تغییر کرد: مقدار `default_og_image` در `config/seo.php` را هم به‌روز کنید
- [ ] تغییرات commit شدند

---

## ۸. خلاصه وضعیت (snapshot)

| دسته | موجود | آپلود نشده |
|---|---|---|
| ویدیوهای hero | ۲ (hero-video + hero-video-click) | ۱ (hero-poster) |
| ویدیوهای sections | ۱ (meet-intro) | ۳ (course-intro, nimvajabee-world, after-registration) |
| ویدیوهای فصل‌ها | ۰ | ۶ (chapter-1 تا chapter-6) |
| ویدیوهای هنرجویان | ۰ | ۳ (student-1 تا student-3) |
| پوسترهای landing | ۰ | ۱۵ (hero + 4 section + 6 chapter + 4 static) |
| پوستر و آواتار هنرجو | ۰ | ۶ (posters×3 + avatars×3) |
| تصاویر پنل هنرجو | ۱ (exercises-header) | ۷ (onboarding + mentor + resources + medals + updates + start-guide-poster + a11 نامشخص) |
| لوگو و برند | ۲ (logo + logo-active) | ۲ (brand/mark + brand/logo — deferred) |
| favicon / apple-touch | ۳ | ۰ |
| OG / SEO image | ۰ | ۱ (animatorsho-og.webp) |
| **جمع** | **۹** | **~۴۴** |

---

*این فایل را هر بار که asset جدیدی آپلود یا تغییر می‌کنید به‌روز کنید.*  
*مرجع کد اصلی: `resources/js/lib/landing-media.ts` · `resources/js/lib/brand-assets.ts` · `config/student_panel.php` · `public/media/student-panel/README.md`*
