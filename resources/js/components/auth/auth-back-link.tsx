import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { AUTH_BACK_TO_HOME_LABEL } from '@/lib/auth-form-data';
import { home } from '@/routes';

export function AuthBackLink() {
    return (
        <Link
            href={home()}
            className="inline-flex items-center gap-1.5 self-start text-sm font-bold text-purple transition-colors hover:text-text"
        >
            <ChevronRight className="size-4 shrink-0" aria-hidden="true" />
            <span>{AUTH_BACK_TO_HOME_LABEL}</span>
        </Link>
    );
}
