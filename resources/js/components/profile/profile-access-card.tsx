import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import type {
    ProfileAccessItem,
    ProfileAccessPostAction,
    ProfileAccessSecondaryAction,
} from '@/lib/profile-data';
import { checkout } from '@/routes';
import { formatTomanPrice } from '@/lib/format-toman';
import { ProfileSectionCard } from '@/components/profile/profile-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';

type ProfileAccessCardProps = {
    accessItems: ProfileAccessItem[];
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
                className="flex h-11 w-full items-center justify-center rounded-pill bg-surface px-4 text-sm font-bold text-red ring-1 ring-red/30 transition-opacity hover:opacity-95"
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

function LicenseKeyCopyField({ licenseKey }: { licenseKey: string }) {
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
        <div className="flex min-w-0 w-full flex-col gap-2">
            <span className="text-xs font-medium text-muted">
                کد لایسنس SpotPlayer
            </span>
            <div
                dir="ltr"
                className="max-h-24 min-w-0 overflow-x-hidden overflow-y-auto rounded-xl bg-surface px-3 py-2 ring-1 ring-border"
            >
                <p className="break-all text-left font-mono text-[11px] leading-relaxed text-text">
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
        </div>
    );
}

export function ProfileAccessCard({ accessItems }: ProfileAccessCardProps) {
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
                            className="flex min-w-0 flex-col gap-3 rounded-2xl bg-surface-warm p-4 ring-1 ring-border/70"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <h3 className="text-sm font-bold text-text">
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
                                />
                            ) : null}

                            {item.primaryAction !== null ? (
                                <AccessItemPostAction
                                    action={item.primaryAction}
                                    variant="primary"
                                />
                            ) : null}

                            {item.secondaryAction !== null ? (
                                <AccessItemCancelAction
                                    action={item.secondaryAction}
                                />
                            ) : null}

                            <AccessItemAction nextAction={item.nextAction} />
                        </li>
                    ))}
                </ul>
            )}
        </ProfileSectionCard>
    );
}
