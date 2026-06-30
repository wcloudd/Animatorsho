import { Link } from '@inertiajs/react';
import { ChevronLeft, Download, MonitorSmartphone, Users } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import type {
    ProfileAccessItem,
    ProfileAccessLinks,
    ProfileAccessPostAction,
    ProfileAccessSecondaryAction,
} from '@/lib/profile-data';
import { checkout } from '@/routes';
import { formatTomanPrice } from '@/lib/format-toman';
import { LandingVideoModal } from '@/components/landing/landing-video-modal';
import { ProfileSectionCard } from '@/components/profile/profile-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import { showCoursePanelComingSoonToast } from '@/components/course/course-home-coming-soon-button';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';

type ProfileAccessCardProps = {
    accessItems: ProfileAccessItem[];
    accessLinks: ProfileAccessLinks;
};

const accessStateAccentClassNames: Record<
    ProfileAccessItem['accessState'],
    string
> = {
    access_active: 'ring-green/25',
    payment_pending: 'ring-gold/30',
    payment_reviewing: 'ring-gold/30',
    installment_down_payment_pending: 'ring-gold/30',
    installment_reviewing: 'ring-gold/30',
    installment_rejected: 'ring-gold/30',
    paid_license_pending: 'ring-gold/30',
    license_revoked: 'ring-red/25',
    payment_failed: 'ring-red/25',
    cancelled: 'ring-border/70',
};

function AccessItemAction({
    nextAction,
}: {
    nextAction: ProfileAccessItem['nextAction'];
}) {
    if (nextAction === null) {
        return null;
    }

    if (nextAction.external) {
        return (
            <a
                href={nextAction.href}
                target="_blank"
                rel="noopener noreferrer"
                className="flex h-10 w-full items-center justify-center rounded-pill bg-surface text-sm font-bold text-green ring-1 ring-border transition-opacity hover:opacity-95"
            >
                {nextAction.label}
            </a>
        );
    }

    return (
        <Link
            href={nextAction.href}
            className="flex h-10 w-full items-center justify-center rounded-pill bg-surface text-sm font-bold text-green ring-1 ring-border transition-opacity hover:opacity-95"
        >
            {nextAction.label}
        </Link>
    );
}

function AccessItemPostAction({
    action,
    variant,
}: {
    action: ProfileAccessPostAction;
    variant: 'primary' | 'secondary';
}) {
    return (
        <Link
            href={action.href}
            method={action.method}
            as="button"
            className={cn(
                'flex h-11 w-full items-center justify-center rounded-pill px-4 text-sm font-bold transition-opacity hover:opacity-95',
                variant === 'primary'
                    ? 'btn-cta-green text-white shadow-soft'
                    : 'bg-surface text-red ring-1 ring-red/30',
            )}
        >
            {action.label}
        </Link>
    );
}

function AccessItemCancelAction({
    action,
}: {
    action: ProfileAccessSecondaryAction;
}) {
    const [confirming, setConfirming] = useState(false);

    if (!confirming) {
        return (
            <button
                type="button"
                onClick={() => setConfirming(true)}
                className="flex h-10 w-full items-center justify-center rounded-pill bg-surface px-4 text-sm font-bold text-red ring-1 ring-red/30 transition-opacity hover:opacity-95"
            >
                {action.label}
            </button>
        );
    }

    return (
        <div className="flex flex-col gap-2 rounded-xl bg-red-soft/50 p-3 ring-1 ring-red/20">
            <p className="text-right text-xs font-medium leading-relaxed text-text">
                با لغو سفارش، ثبت‌نام فعلی حذف می‌شود و می‌توانید دوباره
                ثبت‌نام کنید.
            </p>
            <div className="flex flex-col gap-2">
                <Link
                    href={action.href}
                    method={action.method}
                    as="button"
                    className="flex h-10 w-full items-center justify-center rounded-pill bg-red text-sm font-bold text-white transition-opacity hover:opacity-95"
                >
                    تأیید لغو سفارش
                </Link>
                <button
                    type="button"
                    onClick={() => setConfirming(false)}
                    className="flex h-10 w-full items-center justify-center rounded-pill bg-surface text-sm font-bold text-muted ring-1 ring-border transition-opacity hover:opacity-95"
                >
                    انصراف
                </button>
            </div>
        </div>
    );
}

function LicenseKeyCopyField({
    licenseKey,
    accessLinks,
}: {
    licenseKey: string;
    accessLinks: ProfileAccessLinks;
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
            <span className="text-xs font-bold text-green">
                کد لایسنس SpotPlayer
            </span>
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
                aria-label="کپی کد لایسنس SpotPlayer"
                className={cn(
                    'flex h-10 w-full min-w-0 items-center justify-center rounded-pill px-4 text-sm font-bold transition-opacity hover:opacity-95',
                    copied
                        ? 'bg-green-soft text-green ring-1 ring-green/40'
                        : 'btn-cta-green text-white shadow-soft',
                )}
            >
                {copied ? 'در کلیپ‌بورد ذخیره شد' : 'کپی کد لایسنس'}
            </button>
            <ProfileAccessResourceLinks accessLinks={accessLinks} />
        </div>
    );
}

const compactAccessLinkClassName =
    'flex w-full items-center gap-3 rounded-2xl bg-surface px-3 py-3 text-start ring-1 ring-purple/20 transition-opacity hover:opacity-95';

function ProfileAccessResourceLink({
    title,
    subtitle,
    url,
    icon: Icon,
    onClick,
}: {
    title: string;
    subtitle: string;
    url: string | null;
    icon: typeof MonitorSmartphone;
    onClick?: () => void;
}) {
    const content = (
        <>
            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-purple-soft text-purple ring-1 ring-purple/15">
                <Icon className="size-4" />
            </span>
            <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                <span className="text-sm font-bold text-text">{title}</span>
                <span className="text-[11px] font-medium text-muted">
                    {subtitle}
                </span>
            </span>
            <ChevronLeft className="size-4 shrink-0 text-muted" aria-hidden />
        </>
    );

    if (url) {
        return (
            <a
                href={url}
                target="_blank"
                rel="noreferrer"
                className={compactAccessLinkClassName}
            >
                {content}
            </a>
        );
    }

    return (
        <button
            type="button"
            onClick={onClick ?? showCoursePanelComingSoonToast}
            className={compactAccessLinkClassName}
        >
            {content}
        </button>
    );
}

function ProfileAccessResourceLinks({
    accessLinks: _accessLinks,
}: {
    accessLinks: ProfileAccessLinks;
}) {
    const [showGuide, setShowGuide] = useState(false);

    return (
        <div className="flex flex-col gap-2">
            <p className="text-right text-xs font-medium leading-relaxed text-muted">
                بعد از کپی لایسنس، راهنمای نصب را ببین و وارد گروه دوره شو.
            </p>
            <div className="flex flex-col gap-2">
                <ProfileAccessResourceLink
                    title="دانلود اسپات‌پلیر"
                    subtitle="دانلود نرم‌افزار پخش دوره"
                    url="https://spotplayer.ir/"
                    icon={Download}
                />
                <ProfileAccessResourceLink
                    title="راهنمای نصب اسپات‌پلیر"
                    subtitle="آموزش نصب و فعال‌سازی"
                    url={null}
                    icon={MonitorSmartphone}
                    onClick={() => setShowGuide(true)}
                />
                <ProfileAccessResourceLink
                    title="گروه انیماتورشو"
                    subtitle="عضویت در گروه هنرجوها"
                    url="https://eitaa.com/joinchat/3839165669C51825dc6c7"
                    icon={Users}
                />
            </div>

            {showGuide && (
                <LandingVideoModal
                    videoSrc="/videos/spotplayer-install-guide.mp4"
                    ariaLabel="راهنمای نصب اسپات‌پلیر"
                    autoPlay={false}
                    onClose={() => setShowGuide(false)}
                />
            )}
        </div>
    );
}

function AccessItemActions({ item }: { item: ProfileAccessItem }) {
    const hasPrimary = item.primaryAction !== null;
    const hasSecondary = item.secondaryAction !== null;
    const hasNext = item.nextAction !== null;

    if (!hasPrimary && !hasSecondary && !hasNext) {
        return null;
    }

    return (
        <div className="flex flex-col gap-2 border-t border-border/60 pt-3">
            {(hasPrimary || hasSecondary) && (hasNext || hasPrimary) ? (
                <span className="text-xs font-bold text-muted">اقدامات</span>
            ) : null}

            {hasPrimary ? (
                <AccessItemPostAction
                    action={item.primaryAction!}
                    variant="primary"
                />
            ) : null}

            {hasSecondary ? (
                <AccessItemCancelAction action={item.secondaryAction!} />
            ) : null}

            {hasNext ? <AccessItemAction nextAction={item.nextAction} /> : null}
        </div>
    );
}

export function ProfileAccessCard({
    accessItems,
    accessLinks,
}: ProfileAccessCardProps) {
    return (
        <ProfileSectionCard
            id="access"
            title="دوره‌ها و دسترسی من"
            description="وضعیت دسترسی، پرداخت و لایسنس هر بسته در یک‌جا نمایش داده می‌شود."
        >
            {accessItems.length === 0 ? (
                <div className="flex flex-col gap-4">
                    <div className="flex flex-col gap-2 rounded-2xl bg-surface-warm p-4 text-sm font-medium leading-relaxed text-muted ring-1 ring-border/70">
                        <p>هنوز دسترسی فعالی برای شما ثبت نشده است.</p>
                        <p>
                            بعد از ثبت‌نام یا تأیید پرداخت، وضعیت دسترسی شما
                            اینجا نمایش داده می‌شود.
                        </p>
                    </div>
                    <Link
                        href={checkout()}
                        className={cn(
                            'btn-cta-green flex h-11 w-full items-center justify-center rounded-pill px-4 text-sm font-bold text-white',
                        )}
                    >
                        مشاهده دوره‌ها
                    </Link>
                </div>
            ) : (
                <ul className="flex flex-col gap-4">
                    {accessItems.map((item) => (
                        <li
                            key={item.id}
                            className={cn(
                                'flex min-w-0 flex-col gap-3 rounded-2xl bg-surface-warm p-4 ring-2 ring-border/70',
                                accessStateAccentClassNames[item.accessState],
                            )}
                        >
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <h3 className="min-w-0 flex-1 text-sm font-bold text-text">
                                    {item.title}
                                </h3>
                                <ProfileStatusBadge tone={item.statusTone}>
                                    {item.statusLabel}
                                </ProfileStatusBadge>
                            </div>

                            <p className="text-right text-sm font-medium leading-relaxed text-muted">
                                {item.description}
                            </p>

                            {item.rejectionReason ? (
                                <div className="rounded-xl bg-red-soft/60 px-3 py-2.5 ring-1 ring-red/20">
                                    <p className="text-xs font-bold text-red">
                                        علت رد شدن:
                                    </p>
                                    <p className="mt-1 text-sm font-medium leading-relaxed text-text">
                                        {item.rejectionReason}
                                    </p>
                                </div>
                            ) : null}

                            {item.paymentMethod !== null ||
                            item.amountToman !== null ? (
                                <ul className="flex flex-col gap-1.5">
                                    {item.paymentMethod !== null ? (
                                        <li className="flex items-center gap-2 text-xs font-medium text-text">
                                            <span
                                                aria-hidden
                                                className="size-1.5 shrink-0 rounded-full bg-purple"
                                            />
                                            روش پرداخت: {item.paymentMethod}
                                        </li>
                                    ) : null}
                                    {item.amountToman !== null ? (
                                        <li className="flex items-center gap-2 text-xs font-medium text-text">
                                            <span
                                                aria-hidden
                                                className="size-1.5 shrink-0 rounded-full bg-purple"
                                            />
                                            مبلغ:{' '}
                                            {formatTomanPrice(item.amountToman)}
                                        </li>
                                    ) : null}
                                </ul>
                            ) : null}

                            {item.accessState === 'access_active' &&
                            item.licenseKey ? (
                                <LicenseKeyCopyField
                                    licenseKey={item.licenseKey}
                                    accessLinks={accessLinks}
                                />
                            ) : null}

                            <AccessItemActions item={item} />
                        </li>
                    ))}
                </ul>
            )}
        </ProfileSectionCard>
    );
}
