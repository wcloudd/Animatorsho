import { Link, router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { cn } from '@/lib/utils';

export function ProfileLogoutButton() {
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <Link
            href={logout()}
            as="button"
            onClick={handleLogout}
            data-test="profile-logout-button"
            className={cn(
                'flex h-11 w-full items-center justify-center gap-2 rounded-pill bg-surface text-sm font-bold text-red shadow-soft ring-1 ring-border transition-colors hover:bg-red/5',
            )}
        >
            <LogOut className="size-4" strokeWidth={2} aria-hidden />
            خروج از حساب کاربری
        </Link>
    );
}
