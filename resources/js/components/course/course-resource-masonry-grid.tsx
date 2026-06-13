import { ImageIcon, Play } from 'lucide-react';
import { useState } from 'react';
import { CourseResourceMediaViewer } from '@/components/course/course-resource-media-viewer';
import type { CourseResourceItem } from '@/lib/course-resources-data';
import { cn } from '@/lib/utils';

type CourseResourceMasonryCardProps = {
    resource: CourseResourceItem;
    onOpen: (resource: CourseResourceItem) => void;
};

function CourseResourceMasonryCard({
    resource,
    onOpen,
}: CourseResourceMasonryCardProps) {
    const canOpen = resource.isAvailable && resource.actionUrl !== null;
    const accessibleLabel = `مشاهده ${resource.title}`;

    const mediaPreview = (
        <div className="overflow-hidden rounded-2xl bg-bg">
            {resource.isVideo && resource.actionUrl ? (
                <div className="relative">
                    <video
                        src={resource.actionUrl}
                        preload="metadata"
                        playsInline
                        muted
                        className="block w-full object-contain"
                        aria-hidden="true"
                    />
                    <span
                        className="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/20"
                        aria-hidden="true"
                    >
                        <span className="flex size-10 items-center justify-center rounded-full bg-purple text-white shadow-soft">
                            <Play className="size-4" />
                        </span>
                    </span>
                </div>
            ) : resource.previewUrl ? (
                <img
                    src={resource.previewUrl}
                    alt={resource.title}
                    loading="lazy"
                    className="block w-full object-contain"
                />
            ) : (
                <div
                    className="flex min-h-24 items-center justify-center bg-purple-soft/30 px-3 py-6"
                    aria-hidden="true"
                >
                    <ImageIcon className="size-8 text-purple/50" />
                </div>
            )}
        </div>
    );

    if (!canOpen) {
        return (
            <article
                className="overflow-hidden rounded-2xl opacity-80 ring-1 ring-border/70"
                aria-label={accessibleLabel}
                aria-disabled="true"
            >
                {mediaPreview}
                <span className="sr-only">{accessibleLabel}</span>
            </article>
        );
    }

    return (
        <button
            type="button"
            onClick={() => onOpen(resource)}
            aria-label={accessibleLabel}
            className={cn(
                'block w-full overflow-hidden rounded-2xl bg-bg text-start ring-1 ring-border/70 transition-colors',
                'hover:ring-purple/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple/40',
            )}
        >
            {mediaPreview}
        </button>
    );
}

type CourseResourceMasonryGridProps = {
    resources: CourseResourceItem[];
};

export function CourseResourceMasonryGrid({
    resources,
}: CourseResourceMasonryGridProps) {
    const [selectedResource, setSelectedResource] =
        useState<CourseResourceItem | null>(null);
    const [viewerOpen, setViewerOpen] = useState(false);

    const openViewer = (resource: CourseResourceItem) => {
        setSelectedResource(resource);
        setViewerOpen(true);
    };

    const handleViewerOpenChange = (open: boolean) => {
        setViewerOpen(open);

        if (!open) {
            setSelectedResource(null);
        }
    };

    return (
        <>
            <div className="columns-2 gap-2.5 sm:columns-2 lg:columns-3 [column-fill:balance]">
                {resources.map((resource) => (
                    <div
                        key={resource.id}
                        className="mb-2.5 break-inside-avoid"
                    >
                        <CourseResourceMasonryCard
                            resource={resource}
                            onOpen={openViewer}
                        />
                    </div>
                ))}
            </div>

            <CourseResourceMediaViewer
                resource={selectedResource}
                open={viewerOpen}
                onOpenChange={handleViewerOpenChange}
            />
        </>
    );
}
