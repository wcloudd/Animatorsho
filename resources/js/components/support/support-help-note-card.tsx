import type { SupportHelpNote } from '@/types/support';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-gold-soft px-5 py-6 shadow-soft ring-1 ring-border';

type SupportHelpNoteCardProps = {
    helpNote: SupportHelpNote;
};

export function SupportHelpNoteCard({ helpNote }: SupportHelpNoteCardProps) {
    return (
        <article className={cardClassName}>
            <header className="flex flex-col gap-1.5">
                <h2 className="text-base font-bold text-text">
                    {helpNote.title}
                </h2>
                <p className="text-sm font-medium leading-relaxed text-muted">
                    {helpNote.text}
                </p>
            </header>

            <a
                href={helpNote.ctaHref}
                className={cn(
                    'flex h-11 w-full items-center justify-center rounded-pill bg-surface text-sm font-bold text-text shadow-soft ring-1 ring-border transition-colors hover:bg-purple-soft',
                )}
            >
                {helpNote.ctaLabel}
            </a>
        </article>
    );
}
