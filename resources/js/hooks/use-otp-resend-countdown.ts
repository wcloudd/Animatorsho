import { useEffect, useState } from 'react';
import { secondsUntil } from '@/lib/auth-otp';

export function useOtpResendCountdown(
    resendAvailableAt?: string | null,
): number {
    const [resendSeconds, setResendSeconds] = useState(() =>
        resendAvailableAt ? secondsUntil(resendAvailableAt) : 0,
    );

    useEffect(() => {
        if (!resendAvailableAt) {
            setResendSeconds(0);

            return;
        }

        setResendSeconds(secondsUntil(resendAvailableAt));

        const interval = window.setInterval(() => {
            setResendSeconds(secondsUntil(resendAvailableAt));
        }, 1000);

        return () => window.clearInterval(interval);
    }, [resendAvailableAt]);

    return resendSeconds;
}
