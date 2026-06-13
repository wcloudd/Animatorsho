import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { CourseHomeCompactImageStrip } from '@/components/course/course-home-compact-image-strip';
import type { CourseHomeSectionVisual } from '@/lib/course-home-data';

type CourseHomeSectionCardProps = {
    title: string;
    description?: string;
    visual: CourseHomeSectionVisual;
    placeholderIcon: LucideIcon;
    children: ReactNode;
};

export function CourseHomeSectionCard({
    title,
    description,
    visual,
    placeholderIcon,
    children,
}: CourseHomeSectionCardProps) {
    const hasImage =
        typeof visual.imageUrl === 'string' &&
        visual.imageUrl.trim().length > 0;

    return (
        <article className="flex w-full flex-col overflow-hidden rounded-[28px] bg-surface shadow-soft ring-1 ring-border">
            <CourseHomeCompactImageStrip
                imageUrl={visual.imageUrl}
                imageAlt={visual.imageAlt}
                variant="section"
                showPlaceholder={!hasImage}
                placeholderTitle={visual.placeholderTitle}
                placeholderDescription={visual.placeholderDescription}
                placeholderIcon={placeholderIcon}
            />

            <div className="flex flex-col gap-4 px-5 py-5">
                <header className="flex flex-col gap-1.5">
                    <h2 className="text-base font-bold text-text">{title}</h2>
                    {description ? (
                        <p className="text-sm font-medium leading-relaxed text-muted">
                            {description}
                        </p>
                    ) : null}
                </header>
                {children}
            </div>
        </article>
    );
}
