import { formatFileSize } from '@/lib/format-file-size';
import { cn } from '@/lib/utils';

export type SupportMessageAttachment = {
    id: number;
    originalName: string;
    sizeBytes: number;
    mimeType: string;
    downloadUrl: string;
};

type SupportMessageAttachmentCardProps = {
    attachment: SupportMessageAttachment;
    className?: string;
};

function attachmentTypeLabel(mimeType: string): string {
    if (mimeType.startsWith('image/')) {
        return 'تصویر';
    }

    if (mimeType === 'application/pdf') {
        return 'PDF';
    }

    if (
        mimeType === 'application/zip' ||
        mimeType === 'application/x-zip-compressed'
    ) {
        return 'ZIP';
    }

    return 'فایل';
}

export function SupportMessageAttachmentCard({
    attachment,
    className,
}: SupportMessageAttachmentCardProps) {
    const typeLabel = attachmentTypeLabel(attachment.mimeType);

    return (
        <a
            href={attachment.downloadUrl}
            className={cn(
                'flex items-center gap-3 rounded-xl bg-bg px-3 py-3 ring-1 ring-border/70 transition hover:ring-purple/30',
                className,
            )}
        >
            <span
                className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-purple-soft text-xs font-bold text-purple"
                aria-hidden
            >
                {typeLabel}
            </span>
            <span className="min-w-0 flex-1">
                <span className="block truncate text-sm font-medium text-text">
                    {attachment.originalName}
                </span>
                <span className="text-xs text-muted">
                    {formatFileSize(attachment.sizeBytes)}
                </span>
            </span>
        </a>
    );
}
