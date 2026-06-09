import { useCallback, useState } from 'react';

const FAILURE_THRESHOLD = 2;

export function useAuthSupportFallback() {
    const [failureCount, setFailureCount] = useState(0);

    const onAuthError = useCallback(() => {
        setFailureCount((count) => count + 1);
    }, []);

    const showSupportFallback = failureCount >= FAILURE_THRESHOLD;

    return { showSupportFallback, onAuthError };
}
