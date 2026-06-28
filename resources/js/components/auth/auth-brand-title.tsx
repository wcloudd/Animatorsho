/**
 * Shared auth brand wordmark.
 *
 * Renders the Animatorsho name as a playful sticker text-logo used across all
 * authentication pages (login, register, password recovery, OTP verify, ...).
 * It replaces the old decorative auth logo mark and is wired into the shared
 * `AuthPageIntro` so every auth page stays consistent.
 *
 * Style: an animated multi-color gradient fill layered over a cream
 * outline/glow, with keyframe-style accent dots.
 * The moving gradient (`.auth-brand-text`) and its reduced-motion fallback live
 * in `resources/css/app.css` next to the other gradient-text utilities.
 */
const BRAND_TEXT = 'انیماتورشو';

export function AuthBrandTitle() {
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
                        className="auth-brand-outline absolute inset-0 -z-10"
                    >
                        {BRAND_TEXT}
                    </span>
                    {/* animated gradient fill */}
                    <span className="auth-brand-text relative">
                        {BRAND_TEXT}
                    </span>
                </span>
            </div>
        </div>
    );
}
