import { Link } from '@inertiajs/react';
import { ProfileSectionCard } from '@/components/profile/profile-section-card';
import support from '@/routes/support';
import { cn } from '@/lib/utils';

export function CourseHomeSupportSection() {
    return (
        <ProfileSectionCard
            title="پشتیبانی و راهنمایی"
            description="اگر درباره دسترسی، لایسنس یا محتوای دوره سوالی داری، تیم پشتیبانی کنارت است."
        >
            <Link
                href={support.index()}
                className={cn(
                    'flex h-11 w-full items-center justify-center rounded-pill bg-green text-sm font-bold text-white shadow-soft transition-opacity hover:opacity-95',
                )}
            >
                رفتن به پشتیبانی
            </Link>
        </ProfileSectionCard>
    );
}
