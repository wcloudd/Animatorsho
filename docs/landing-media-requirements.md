# Landing Media & Brand Asset Requirements

Pre-launch checklist for final photos, videos, logos, and social preview images on the Animatorsho landing page.

**Code references:** `resources/js/lib/landing-media.ts` and `resources/js/lib/brand-assets.ts`.

Until files are uploaded, the UI shows poster images when available, then clean placeholders — no broken media.

---

## Brand & browser chrome

| Asset | Path | Size / format | Where used | Status |
| --- | --- | --- | --- | --- |
| Main site logo (wordmark) | `public/images/animatorsho-logo.svg` | SVG, full wordmark | Bottom nav (inactive), auth simple layout | **Present** |
| Active nav logo | `public/images/animatorsho-logo-active.svg` | SVG | Bottom nav (active tab) | **Present** |
| Main logo (target folder) | `public/images/brand/animatorsho-logo.svg` | SVG preferred | Future migration path | Deferred |
| Logo mark (compact) | `public/images/brand/animatorsho-mark.svg` | SVG, square mark | Future header / small UI | Deferred |
| Favicon ICO | `public/favicon.ico` | Multi-size 16×16, 32×32, 48×48 | Browser tab (`resources/views/app.blade.php`) | **Present** |
| Favicon SVG | `public/favicon.svg` | Square vector | Browser tab (modern browsers) | **Present** |
| Apple touch icon | `public/apple-touch-icon.png` | 180×180 PNG | iOS home screen | **Present** |
| OG / social share image | `public/images/seo/animatorsho-og.webp` | 1200×630 WebP | Open Graph, Twitter cards, JSON-LD | Deferred (fallback: logo SVG) |

### OG image notes

- Target constant: `BRAND_OG_IMAGE_PATH` in `brand-assets.ts`
- Active fallback: `BRAND_OG_IMAGE_FALLBACK_PATH` → `/images/animatorsho-logo.svg`
- Backend fallback: `config/seo.php` → `default_og_image`
- After uploading `animatorsho-og.webp`, update `default_og_image` and switch frontend default to `BRAND_OG_IMAGE_PATH`.

### Favicon head tags

Configured in `resources/views/app.blade.php`:

```html
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
```

---

## Hero (top of landing)

| Asset | Path | Recommended | Appears on |
| --- | --- | --- | --- |
| Hero loop video | `public/media/landing/hero-video.mp4` | MP4 H.264, ~4:3, ≤15s loop, muted-friendly | Hero card above H1 |
| Hero poster | `public/media/landing/hero-poster.webp` | WebP ~780×585 (4:3) | Video poster + fallback |

**Component:** `LandingMediaVideo` with `placeholderVariant="video"` in `animatorsho/index.tsx`.

---

## Section videos (looping previews)

All section videos use `LandingMediaVideo`: `autoPlay`, `muted`, `loop`, `playsInline`, `preload="none"`, lazy viewport activation, poster fallback.

| Slot | Video path | Poster path | Landing section |
| --- | --- | --- | --- |
| Meet intro | `public/media/landing/videos/meet-intro.mp4` | `public/media/landing/posters/meet-intro.webp` | «با انیماتورشو بیشتر آشناشو» |
| Course overview | `public/media/landing/videos/course-intro.mp4` | `public/media/landing/posters/course-intro.webp` | «ساخت انیمیشن از ایده تا اولین خروجی» |
| Nimvajabee world | `public/media/landing/videos/nimvajabee-world.mp4` | `public/media/landing/posters/nimvajabee-world.webp` | «یادگیری از خالق نیم‌وجبی» |
| After registration | `public/media/landing/videos/after-registration.mp4` | `public/media/landing/posters/after-registration.webp` | «بعد از ثبت‌نام چه اتفاقی می‌افته؟» |

**Recommended video specs:** MP4 H.264, square or 4:5 crop for mobile, 5–20s loops, target ≤2–4 MB each after compression.

**Recommended poster specs:** WebP, match video aspect, width ~780px for 390px viewport @2x.

---

## Course chapter tabs

Six chapter preview slots (`LANDING_COURSE_CHAPTERS` in `landing-media.ts`):

| Chapter | Video | Poster |
| --- | --- | --- |
| فصل اول | `videos/chapter-1.mp4` | `posters/chapter-1.webp` |
| فصل دوم | `videos/chapter-2.mp4` | `posters/chapter-2.webp` |
| فصل سوم | `videos/chapter-3.mp4` | `posters/chapter-3.webp` |
| فصل چهارم | `videos/chapter-4.mp4` | `posters/chapter-4.webp` |
| ورکشاپ ۱ | `videos/chapter-5.mp4` | `posters/chapter-5.webp` |
| ورکشاپ ۲ | `videos/chapter-6.mp4` | `posters/chapter-6.webp` |

Paths are relative to `public/media/landing/`.

---

## Student work samples

Three carousel cards + modal playback (`StudentWorksSection`):

| Student slot | Video | Poster | Avatar |
| --- | --- | --- | --- |
| student-1 | `student-works/videos/student-1.mp4` | `student-works/posters/student-1.webp` | `student-works/avatars/student-1.webp` |
| student-2 | `student-works/videos/student-2.mp4` | `student-works/posters/student-2.webp` | `student-works/avatars/student-2.webp` |
| student-3 | `student-works/videos/student-3.mp4` | `student-works/posters/student-3.webp` | `student-works/avatars/student-3.webp` |

Paths are relative to `public/media/landing/`.

**Modal video:** `controls`, `autoPlay`, `playsInline` — user-initiated playback.

**Avatar fallback:** first letter of student name when image missing.

---

## Static section illustrations

| Slot | Path | Section |
| --- | --- | --- |
| FAQ | `public/media/landing/posters/faq-section.webp` | سوالات پرتکرار |
| Consultation CTA | `public/media/landing/posters/consultation-section.webp` | مشاوره رایگان |
| Final CTA background | `public/media/landing/posters/final-cta-section.webp` | ثبت‌نام پایین صفحه |
| Purchase key art | `public/media/landing/posters/purchase-section-key.webp` | بخش خرید (checkout-related UI) |

**Recommended:** WebP, 4:3 for FAQ/consultation (~780×585), full-bleed width for final CTA background.

---

## Compression guidelines

- **WebP posters:** quality 75–85, sRGB, no unnecessary alpha.
- **MP4 loops:** H.264, no audio track (or silent), 24–30 fps, `-movflags +faststart` for web.
- **OG image:** 1200×630, keep text/logos inside safe center; avoid tiny text (mobile share crops).
- **SVG logos:** optimize with SVGO; prefer single-color or simple gradients for small sizes.
- **Favicon.ico:** bundle 16, 32, 48 px; verify contrast on light/dark browser chrome.

---

## Upload workflow (no admin UI in this slice)

1. Export assets to the paths above under `public/`.
2. Verify locally: no 404 in Network tab; posters appear before videos load.
3. For OG: upload `animatorsho-og.webp`, then update `config/seo.php` `default_og_image`.
4. Run `npm run build` and spot-check `/` on a 390px viewport.

---

## Directory layout

```
public/
├── favicon.ico
├── favicon.svg
├── apple-touch-icon.png
├── images/
│   ├── animatorsho-logo.svg          (current)
│   ├── animatorsho-logo-active.svg   (current)
│   ├── brand/                        (future logos)
│   └── seo/
│       └── animatorsho-og.webp       (deferred)
└── media/
    └── landing/
        ├── hero-video.mp4
        ├── hero-poster.webp
        ├── videos/
        ├── posters/
        └── student-works/
            ├── videos/
            ├── posters/
            └── avatars/
```

Placeholder `.gitkeep` files keep empty folders in git until media is added.
