import { Play } from 'lucide-react';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { CourseResourceItem } from '@/lib/course-resources-data';
import { cn } from '@/lib/utils';

type CourseResourceMediaViewerProps = {
    resource: CourseResourceItem | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function CourseResourceMediaViewer({
    resource,
    open,
    onOpenChange,
}: CourseResourceMediaViewerProps) {
    if (resource === null) {
        return null;
    }

    const mediaUrl = resource.previewUrl ?? resource.actionUrl;
    const hasDescription = resource.description.trim() !== '';
    const hasPublishedAt = resource.publishedAtLabel !== '—';
    const hasMeta =
        resource.categoryLabel !== null || hasPublishedAt || resource.fileExtension;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[calc(100dvh-1.5rem)] max-w-[calc(100%-1.5rem)] gap-4 overflow-y-auto rounded-[24px] border-border bg-surface p-4 sm:max-w-md">
                <DialogHeader className="text-start">
                    <DialogTitle className="text-base font-bold text-text">
                        {resource.title}
                    </DialogTitle>
                    {hasDescription ? (
                        <DialogDescription className="whitespace-pre-wrap text-sm font-medium leading-relaxed text-muted">
                            {resource.description}
                        </DialogDescription>
                    ) : (
                        <DialogDescription className="sr-only">
                            {resource.typeLabel}
                        </DialogDescription>
                    )}
                </DialogHeader>

                {mediaUrl ? (
                    <div className="overflow-hidden rounded-2xl bg-bg ring-1 ring-border/70">
                        {resource.isVideo ? (
                            <video
                                key={mediaUrl}
                                className="max-h-[min(70dvh,28rem)] w-full object-contain"
                                controls
                                playsInline
                                preload="metadata"
                            >
                                <source src={mediaUrl} />
                            </video>
                        ) : (
                            <img
                                src={mediaUrl}
                                alt={resource.title}
                                className="block w-full object-contain"
                            />
                        )}
                    </div>
                ) : (
                    <div className="flex min-h-32 items-center justify-center rounded-2xl bg-bg px-4 py-6 text-center ring-1 ring-border/70">
                        <span className="flex size-10 items-center justify-center rounded-full bg-purple text-white">
                            <Play className="size-4" />
                        </span>
                    </div>
                )}

                {hasMeta ? (
                    <div
                        className={cn(
                            'flex flex-wrap items-center gap-1.5',
                            !hasDescription && 'pt-0',
                        )}
                    >
                        <ProfileStatusBadge tone="neutral">
                            {resource.typeLabel}
                        </ProfileStatusBadge>
                        {resource.fileExtension ? (
                            <ProfileStatusBadge tone="neutral">
                                {resource.fileExtension}
                            </ProfileStatusBadge>
                        ) : null}
                        {resource.categoryLabel ? (
                            <span className="text-[11px] font-medium text-muted">
                                {resource.categoryLabel}
                            </span>
                        ) : null}
                        {hasPublishedAt ? (
                            <span className="text-[11px] font-medium text-muted">
                                {resource.publishedAtLabel}
                            </span>
                        ) : null}
                    </div>
                ) : null}
            </DialogContent>
        </Dialog>
    );
}
