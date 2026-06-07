type AdminEmptyStateProps = {
    message: string;
    isSearchActive?: boolean;
};

export function AdminEmptyState({
    message,
    isSearchActive = false,
}: AdminEmptyStateProps) {
    const displayMessage = isSearchActive
        ? 'نتیجه‌ای برای جستجو یافت نشد.'
        : message;

    return (
        <p className="rounded-2xl bg-surface px-4 py-6 text-center text-sm text-muted ring-1 ring-border/70">
            {displayMessage}
        </p>
    );
}
