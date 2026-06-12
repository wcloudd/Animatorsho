import { usePage } from '@inertiajs/react';
import { useRef } from 'react';
import type { SharedPageProps } from '@/types/shared';

export function useHoneypotField() {
    const ref = useRef<HTMLInputElement>(null);
    const { security } = usePage<SharedPageProps>().props;
    const honeypot = security?.honeypot;
    const enabled = honeypot?.enabled === true;
    const fieldName = honeypot?.fieldName ?? '';

    const field =
        enabled && fieldName !== '' ? (
            <input
                ref={ref}
                type="text"
                name={fieldName}
                defaultValue=""
                autoComplete="off"
                tabIndex={-1}
                aria-hidden="true"
                className="pointer-events-none absolute start-[-9999px] h-px w-px overflow-hidden opacity-0"
            />
        ) : null;

    const withHoneypot = <T extends Record<string, unknown>>(data: T): T => {
        if (! enabled || fieldName === '') {
            return data;
        }

        return {
            ...data,
            [fieldName]: ref.current?.value ?? '',
        };
    };

    return { field, withHoneypot };
}
