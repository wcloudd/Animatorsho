import { Link } from '@inertiajs/react';
import { profile } from '@/routes';

export function CourseHomeProfileFootnote() {
    return (
        <p className="px-1 text-center text-xs font-medium leading-relaxed text-muted">
            لایسنس و وضعیت دسترسی در{' '}
            <Link
                href={profile()}
                className="font-bold text-purple underline decoration-purple/30 underline-offset-2 transition-colors hover:decoration-purple"
            >
                پروفایل
            </Link>
        </p>
    );
}
