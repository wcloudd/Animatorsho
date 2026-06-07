import { useEffect } from 'react';

export function useAdminListFocus(focusId: number | null): void {
    useEffect(() => {
        if (focusId === null) {
            return;
        }

        const element = document.getElementById(`admin-item-${focusId}`);

        if (!element) {
            return;
        }

        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, [focusId]);
}
