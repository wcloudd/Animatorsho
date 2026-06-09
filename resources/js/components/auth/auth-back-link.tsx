import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { AUTH_BACK_TO_HOME_LABEL } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

type AuthBackLinkProps = {
    className?: string;
};

export function AuthBackLink({ className }: AuthBackLinkProps) {
    return (
        <Link
            href={home()}
            className={cn(
                'inline-flex items-center gap-1.5 text-sm font-bold text-purple transition-colors hover:text-text',
                className,
            )}
        >
            <ChevronRight className="size-4 shrink-0" aria-hidden="true" />
            <span>{AUTH_BACK_TO_HOME_LABEL}</span>
        </Link>
    );
}
