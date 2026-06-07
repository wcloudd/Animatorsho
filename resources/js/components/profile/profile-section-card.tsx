import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border';

type ProfileSectionCardProps = {
    id?: string;
    title: string;
    description?: string;
    children: ReactNode;
    className?: string;
};

export function ProfileSectionCard({
    id,
    title,
    description,
    children,
    className,
}: ProfileSectionCardProps) {
    return (
        <article id={id} className={cn(cardClassName, className)}>
            <header className="flex flex-col gap-1.5">
                <h2 className="text-base font-bold text-text">{title}</h2>
                {description ? (
                    <p className="text-sm font-medium leading-relaxed text-muted">
                        {description}
                    </p>
                ) : null}
            </header>
            {children}
        </article>
    );
}
