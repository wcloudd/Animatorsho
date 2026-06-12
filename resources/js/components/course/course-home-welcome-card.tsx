import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type { CourseHomeWelcome } from '@/lib/course-home-data';

type CourseHomeWelcomeCardProps = {
    welcome: CourseHomeWelcome;
};

function firstNameFromDisplayName(displayName: string): string {
    const parts = displayName.trim().split(/\s+/).filter(Boolean);

    return parts[0] ?? displayName;
}

export function CourseHomeWelcomeCard({ welcome }: CourseHomeWelcomeCardProps) {
    const firstName = firstNameFromDisplayName(welcome.displayName);

    return (
        <article className="relative overflow-hidden rounded-[28px] bg-surface shadow-soft ring-1 ring-border">
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-gradient-to-bl from-purple-soft via-surface to-gold-soft/50"
            />
            <div
                aria-hidden
                className="pointer-events-none absolute -start-8 -top-6 size-32 rounded-full bg-purple/10 blur-2xl"
            />

            <div className="relative flex flex-col gap-4 px-5 py-6">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="inline-flex w-fit items-center rounded-pill bg-purple-soft px-2.5 py-0.5 text-[11px] font-bold text-purple ring-1 ring-purple/15">
                        فضای دوره
                    </span>
                    {welcome.hasFullAccess ? (
                        <ProfileStatusBadge tone="success">
                            دسترسی کامل
                        </ProfileStatusBadge>
                    ) : (
                        <ProfileStatusBadge tone="success">
                            دسترسی فعال
                        </ProfileStatusBadge>
                    )}
                </div>

                <h1 className="font-display text-[1.5rem] leading-tight font-bold text-text">
                    سلام، {firstName}!
                </h1>

                <p className="text-sm font-medium leading-relaxed text-muted">
                    {welcome.hasFullAccess
                        ? 'به دوره جامع انیماتورشو خوش آمدی. همه فصل‌ها برایت فعال است و می‌توانی یادگیری را ادامه بدهی.'
                        : 'دسترسی دوره برایت فعال است. از اینجا می‌توانی یادگیری را ادامه بدهی و بخش‌های جدید را دنبال کنی.'}
                </p>
            </div>
        </article>
    );
}
