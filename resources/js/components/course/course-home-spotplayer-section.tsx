import { useState } from 'react';
import { toast } from 'sonner';
import { ProfileSectionCard } from '@/components/profile/profile-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type { CourseHomeSpotPlayerLicense } from '@/lib/course-home-data';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';

type CourseHomeSpotPlayerSectionProps = {
    licenses: CourseHomeSpotPlayerLicense[];
};

function LicenseKeyCopyField({
    packageTitle,
    licenseKey,
}: {
    packageTitle: string;
    licenseKey: string;
}) {
    const [, copy] = useClipboard();
    const [copied, setCopied] = useState(false);

    const handleCopy = async () => {
        const copiedSuccessfully = await copy(licenseKey);

        if (!copiedSuccessfully) {
            toast.error('کپی انجام نشد. لطفاً دوباره تلاش کنید.');

            return;
        }

        setCopied(true);
        toast.success('کد لایسنس در کلیپ‌بورد ذخیره شد');
        window.setTimeout(() => setCopied(false), 2000);
    };

    return (
        <div className="flex min-w-0 w-full flex-col gap-2 rounded-xl bg-green-soft/60 p-3 ring-1 ring-green/20">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="text-xs font-bold text-green">
                    {packageTitle}
                </span>
                <ProfileStatusBadge tone="success">فعال</ProfileStatusBadge>
            </div>
            <div
                dir="ltr"
                className="max-h-28 min-w-0 overflow-x-hidden overflow-y-auto rounded-xl bg-surface px-3 py-2.5 ring-1 ring-border"
            >
                <p className="break-all text-left font-mono text-xs leading-relaxed text-text">
                    {licenseKey}
                </p>
            </div>
            <button
                type="button"
                onClick={handleCopy}
                aria-label={`کپی کد لایسنس ${packageTitle}`}
                className={cn(
                    'flex h-10 w-full min-w-0 items-center justify-center rounded-pill px-4 text-sm font-bold transition-opacity hover:opacity-95',
                    copied
                        ? 'bg-green-soft text-green ring-1 ring-green/40'
                        : 'btn-cta-green text-white shadow-soft',
                )}
            >
                {copied ? 'در کلیپ‌بورد ذخیره شد' : 'کپی کد لایسنس'}
            </button>
        </div>
    );
}

export function CourseHomeSpotPlayerSection({
    licenses,
}: CourseHomeSpotPlayerSectionProps) {
    return (
        <ProfileSectionCard
            title="دسترسی SpotPlayer"
            description="برای تماشای ویدیوهای دوره، کد لایسنس را در اپلیکیشن SpotPlayer وارد کن."
        >
            <div className="flex flex-col gap-3">
                {licenses.map((license) => (
                    <LicenseKeyCopyField
                        key={license.packageTitle}
                        packageTitle={license.packageTitle}
                        licenseKey={license.licenseKey}
                    />
                ))}
            </div>
        </ProfileSectionCard>
    );
}
