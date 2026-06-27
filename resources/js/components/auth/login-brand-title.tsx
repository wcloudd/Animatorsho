/**
 * Login-only brand wordmark.
 *
 * Replaces the decorative auth logo mark on the login header with the
 * Animatorsho name styled as a playful sticker text-logo: an animated
 * multi-color gradient fill layered over a cream outline/glow, with a few
 * keyframe-style accent dots and a pencil-stroke underline. Kept separate
 * from the shared `AuthIllustration` so other auth pages are unaffected.
 *
 * The moving gradient (`.login-brand-text`) and its reduced-motion fallback
 * live in `resources/css/app.css` next to the other gradient-text utilities.
 */
const BRAND_TEXT = 'انیماتورشو';

export function LoginBrandTitle() {
    return (
        <div
            className="mx-auto flex w-full flex-col items-center gap-2 py-1"
            dir="rtl"
        >
            <div className="relative inline-flex items-center justify-center [filter:drop-shadow(0_4px_10px_rgba(96,55,168,0.18))]">
                {/* keyframe-style accent dots around the wordmark */}
                <span
                    aria-hidden="true"
                    className="absolute -top-2 -right-3 size-2.5 rounded-full bg-gold ring-2 ring-gold/25"
                />
                <span
                    aria-hidden="true"
                    className="absolute -top-1 right-7 size-1.5 rounded-full bg-red/80"
                />
                <span
                    aria-hidden="true"
                    className="absolute -bottom-1 -left-3 size-2 rounded-full bg-blue ring-2 ring-blue/25"
                />
                <span
                    aria-hidden="true"
                    className="absolute top-1 -left-1 size-1.5 rounded-full bg-purple/70"
                />

                <span className="relative inline-block font-display text-4xl font-black leading-[1.15] tracking-tight sm:text-5xl">
                    {/* cream outline + soft purple sticker shadow, behind the fill */}
                    <span
                        aria-hidden="true"
                        className="login-brand-outline absolute inset-0 -z-10"
                    >
                        {BRAND_TEXT}
                    </span>
                    {/* animated gradient fill */}
                    <span className="login-brand-text relative">
                        {BRAND_TEXT}
                    </span>
                </span>
            </div>

            {/* playful pencil-motion underline */}
            <span
                aria-hidden="true"
                className="login-brand-underline block w-32 sm:w-40"
            />
        </div>
    );
}
