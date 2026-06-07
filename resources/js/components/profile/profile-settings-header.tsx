import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { profile } from '@/routes';

type ProfileSettingsHeaderProps = {
    title?: string;
};

export function ProfileSettingsHeader({
    title = 'تنظیمات حساب',
}: ProfileSettingsHeaderProps) {
    return (
        <header className="flex flex-col gap-3">
            <Link
                href={profile()}
                className="inline-flex w-fit items-center gap-1 text-sm font-medium text-muted transition-colors hover:text-purple"
            >
                <ChevronRight className="size-4 rotate-180" aria-hidden />
                بازگشت به پروفایل
            </Link>
            <h1 className="font-display text-[1.375rem] font-bold leading-tight text-text">
                {title}
            </h1>
        </header>
    );
}
