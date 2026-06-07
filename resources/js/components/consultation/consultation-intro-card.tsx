import { CONSULTATION_INTRO_BADGES } from '@/lib/consultation-form-data';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-purple-soft px-5 py-6 shadow-soft ring-1 ring-border';

export function ConsultationIntroCard() {
    return (
        <article className={cardClassName}>
            <header className="flex flex-col gap-1.5">
                <h2 className="text-base font-bold text-text">
                    برای چه کسی مناسبه؟
                </h2>
                <p className="text-sm font-medium leading-relaxed text-muted">
                    این فرم برای کسیه که می‌خواد بدونه دوره جامع، خرید فصل
                    جداگانه یا مسیر اقساطی برای شرایطش مناسب‌تره.
                </p>
            </header>

            <ul className="flex flex-wrap gap-2">
                {CONSULTATION_INTRO_BADGES.map((badge) => (
                    <li key={badge.id}>
                        <span
                            className={cn(
                                'inline-flex rounded-pill bg-surface px-2.5 py-1 text-xs font-bold text-text ring-1 ring-border',
                            )}
                        >
                            {badge.label}
                        </span>
                    </li>
                ))}
            </ul>
        </article>
    );
}
