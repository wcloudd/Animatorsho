import { Inbox } from 'lucide-react';
import { cn } from '@/lib/utils';

type AdminEmptyStateProps = {
    message: string;
    isSearchActive?: boolean;
    className?: string;
};

export function AdminEmptyState({
    message,
    isSearchActive = false,
    className,
}: AdminEmptyStateProps) {
    const displayMessage = isSearchActive
        ? 'نتیجه‌ای برای جستجوی شما پیدا نشد. عبارت دیگری امتحان کنید.'
        : message;

    return (
        <div
            className={cn(
                'flex flex-col items-center gap-2 rounded-2xl bg-surface px-4 py-8 text-center ring-1 ring-border/70',
                className,
            )}
        >
            <Inbox
                className="size-8 text-purple/40"
                strokeWidth={1.5}
                aria-hidden
            />
            <p className="max-w-xs text-sm leading-relaxed text-muted">
                {displayMessage}
            </p>
        </div>
    );
}
