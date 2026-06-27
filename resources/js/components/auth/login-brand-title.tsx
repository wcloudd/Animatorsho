/**
 * Login-only brand wordmark.
 *
 * Replaces the decorative auth logo mark on the login header with the
 * Animatorsho name as large, rainbow-gradient Persian text. Kept separate
 * from the shared `AuthIllustration` so other auth pages are unaffected.
 */
export function LoginBrandTitle() {
    return (
        <div
            className="mx-auto flex w-full justify-center py-1 [filter:drop-shadow(0_6px_16px_rgba(96,55,168,0.18))]"
            dir="rtl"
        >
            <span className="bg-[linear-gradient(100deg,var(--color-purple)_0%,var(--color-red)_38%,var(--color-gold)_66%,var(--color-blue)_100%)] bg-clip-text font-display text-4xl font-black leading-[1.15] tracking-tight text-transparent [-webkit-background-clip:text] [-webkit-text-fill-color:transparent] sm:text-5xl">
                انیماتورشو
            </span>
        </div>
    );
}
