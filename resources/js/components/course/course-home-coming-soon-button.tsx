import { toast } from 'sonner';
import { cn } from '@/lib/utils';

export const COURSE_PANEL_COMING_SOON_MESSAGE =
    'این بخش به‌زودی فعال می‌شود';

export function showCoursePanelComingSoonToast(): void {
    toast.info(COURSE_PANEL_COMING_SOON_MESSAGE);
}

type CourseHomeComingSoonButtonProps = {
    children: string;
    variant?: 'primary' | 'secondary' | 'ghost';
    className?: string;
};

const variantClassNames: Record<
    NonNullable<CourseHomeComingSoonButtonProps['variant']>,
    string
> = {
    primary: 'btn-cta-purple text-white shadow-soft',
    secondary:
        'bg-surface text-purple ring-1 ring-purple/25 hover:bg-purple-soft/60',
    ghost: 'bg-bg text-muted ring-1 ring-border/70 hover:bg-purple-soft/40',
};

export function CourseHomeComingSoonButton({
    children,
    variant = 'secondary',
    className,
}: CourseHomeComingSoonButtonProps) {
    return (
        <button
            type="button"
            onClick={showCoursePanelComingSoonToast}
            className={cn(
                'flex h-10 w-full items-center justify-center rounded-pill px-4 text-sm font-bold transition-opacity hover:opacity-95',
                variantClassNames[variant],
                className,
            )}
        >
            {children}
        </button>
    );
}
