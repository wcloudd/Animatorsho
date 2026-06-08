/**
 * Central brand asset paths for logos, favicons, and social preview images.
 * Only reference paths here that exist in `public/` or have a documented fallback.
 */

/** Full wordmark — auth layouts, bottom nav (inactive). */
export const BRAND_LOGO_SRC = '/images/animatorsho-logo.svg?v=2' as const;

/** Wordmark variant for active bottom-nav state. */
export const BRAND_LOGO_ACTIVE_SRC =
    '/images/animatorsho-logo-active.svg?v=2' as const;

/** Compact mark for headers (future). Falls back to wordmark until uploaded. */
export const BRAND_LOGO_MARK_SRC = '/images/brand/animatorsho-mark.svg' as const;

/** Target full logo path after brand folder migration (future). */
export const BRAND_LOGO_TARGET_SRC =
    '/images/brand/animatorsho-logo.svg' as const;

export const BRAND_FAVICON_ICO = '/favicon.ico' as const;
export const BRAND_FAVICON_SVG = '/favicon.svg' as const;
export const BRAND_APPLE_TOUCH_ICON = '/apple-touch-icon.png' as const;

/** Dedicated Open Graph / social share image (upload before launch). */
export const BRAND_OG_IMAGE_PATH = '/images/seo/animatorsho-og.webp' as const;

/** Safe fallback until {@link BRAND_OG_IMAGE_PATH} exists. */
export const BRAND_OG_IMAGE_FALLBACK_PATH =
    '/images/animatorsho-logo.svg' as const;

/** @deprecated Use {@link BRAND_LOGO_SRC} */
export const ANIMATORSHO_LOGO_SRC = BRAND_LOGO_SRC;
