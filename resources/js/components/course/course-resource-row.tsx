import { ExternalLink, File, FileText, Image } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type { CourseResourceItem } from '@/lib/course-resources-data';
import { cn } from '@/lib/utils';

const resourceIconByType: Record<string, LucideIcon> = {
    pdf: FileText,
    file: File,
    image: Image,
    link: ExternalLink,
    external_link: ExternalLink,
    project_file: File,
};

function ResourceIcon({ type }: { type: string }) {
    const Icon = resourceIconByType[type] ?? File;

    return <Icon className="size-4" />;
}

type CourseResourceRowProps = {
    resource: CourseResourceItem;
    showCategory?: boolean;
};

export function CourseResourceRow({
    resource,
    showCategory = true,
}: CourseResourceRowProps) {
    const content = (
        <>
            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-surface text-purple ring-1 ring-purple/10">
                <ResourceIcon type={resource.type} />
            </span>
            <span className="flex min-w-0 flex-1 flex-col gap-1">
                <span className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-bold text-text">
                        {resource.title}
                    </span>
                    <ProfileStatusBadge tone="neutral">
                        {resource.typeLabel}
                    </ProfileStatusBadge>
                </span>
                {showCategory && resource.categoryLabel ? (
                    <span className="text-[11px] font-medium text-muted">
                        {resource.categoryLabel}
                    </span>
                ) : null}
                {resource.description ? (
                    <span className="text-xs font-medium leading-relaxed text-muted">
                        {resource.description}
                    </span>
                ) : null}
                {resource.publishedAtLabel !== '—' ? (
                    <span className="text-[11px] font-medium text-muted">
                        {resource.publishedAtLabel}
                    </span>
                ) : null}
            </span>
            {resource.isAvailable ? (
                <span className="shrink-0 rounded-pill bg-purple px-3 py-1.5 text-xs font-bold text-white">
                    {resource.actionLabel}
                </span>
            ) : (
                <span className="shrink-0 rounded-pill bg-bg px-3 py-1.5 text-xs font-bold text-muted ring-1 ring-border/70">
                    به‌زودی
                </span>
            )}
        </>
    );

    const rowClassName =
        'flex w-full items-center gap-3 rounded-2xl bg-bg px-4 py-3 text-start ring-1 ring-border/70 transition-colors';

    if (resource.isAvailable && resource.actionUrl) {
        const isExternal =
            resource.type === 'external_link' ||
            resource.actionUrl.startsWith('http');

        return (
            <a
                href={resource.actionUrl}
                target={isExternal ? '_blank' : undefined}
                rel={isExternal ? 'noopener noreferrer' : undefined}
                className={cn(rowClassName, 'hover:bg-purple-soft/30')}
            >
                {content}
            </a>
        );
    }

    return (
        <div
            className={cn(rowClassName, 'cursor-default opacity-80')}
            aria-disabled="true"
        >
            {content}
        </div>
    );
}
