import { useCallback, useId, useRef, useState } from 'react';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { authLabelClassName } from '@/components/auth/auth-form-card';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const OTP_LENGTH = 6;

type AuthOtpCodeFieldProps = {
    id?: string;
    label: string;
    placeholder: string;
    error?: string;
    tabIndex?: number;
    'data-test'?: string;
};

export function AuthOtpCodeField({
    id = 'code',
    label,
    error,
    tabIndex = 1,
    'data-test': dataTest,
}: AuthOtpCodeFieldProps) {
    const groupId = useId();
    const inputRefs = useRef<Array<HTMLInputElement | null>>([]);
    const [digits, setDigits] = useState<string[]>(
        Array.from({ length: OTP_LENGTH }, () => ''),
    );

    const codeValue = digits.join('');

    const focusInput = useCallback((index: number) => {
        const input = inputRefs.current[index];

        if (input) {
            input.focus();
            input.select();
        }
    }, []);

    const updateDigits = useCallback(
        (nextDigits: string[]) => {
            setDigits(nextDigits);

            const nextIndex = nextDigits.findIndex((digit) => digit === '');

            if (nextIndex === -1) {
                inputRefs.current[OTP_LENGTH - 1]?.blur();
            } else {
                focusInput(nextIndex);
            }
        },
        [focusInput],
    );

    const applyCode = useCallback(
        (rawValue: string) => {
            const sanitized = rawValue.replace(/\D/g, '').slice(0, OTP_LENGTH);
            const nextDigits = Array.from({ length: OTP_LENGTH }, (_, index) => {
                return sanitized[index] ?? '';
            });

            updateDigits(nextDigits);
        },
        [updateDigits],
    );

    const handleChange = (index: number, value: string) => {
        const sanitized = value.replace(/\D/g, '');

        if (sanitized.length > 1) {
            applyCode(sanitized);

            return;
        }

        const nextDigits = [...digits];
        nextDigits[index] = sanitized;

        if (sanitized && index < OTP_LENGTH - 1) {
            setDigits(nextDigits);
            focusInput(index + 1);

            return;
        }

        setDigits(nextDigits);
    };

    const handleKeyDown = (
        index: number,
        event: React.KeyboardEvent<HTMLInputElement>,
    ) => {
        if (event.key === 'Backspace' && digits[index] === '' && index > 0) {
            event.preventDefault();
            focusInput(index - 1);
        }
    };

    const handlePaste = (event: React.ClipboardEvent<HTMLInputElement>) => {
        event.preventDefault();
        applyCode(event.clipboardData.getData('text'));
    };

    return (
        <div className="grid gap-2">
            <Label htmlFor={`${groupId}-0`} className={authLabelClassName}>
                {label}
            </Label>

            <input type="hidden" name="code" value={codeValue} />

            <div
                className="flex items-center justify-center gap-2"
                dir="ltr"
                role="group"
                aria-label={label}
                data-test={dataTest}
            >
                {digits.map((digit, index) => (
                    <input
                        key={`${groupId}-${index}`}
                        ref={(element) => {
                            inputRefs.current[index] = element;
                        }}
                        id={index === 0 ? id : `${groupId}-${index}`}
                        type="text"
                        inputMode="numeric"
                        autoComplete={index === 0 ? 'one-time-code' : 'off'}
                        pattern="[0-9]*"
                        maxLength={1}
                        value={digit}
                        tabIndex={tabIndex + index}
                        autoFocus={index === 0}
                        aria-label={`${label} ${index + 1}`}
                        onChange={(event) =>
                            handleChange(index, event.target.value)
                        }
                        onKeyDown={(event) => handleKeyDown(index, event)}
                        onPaste={handlePaste}
                        className={cn(
                            'h-11 w-10 rounded-xl border border-border bg-surface text-center text-lg font-bold text-text shadow-xs ring-1 ring-border focus-visible:border-purple focus-visible:ring-purple/25 focus-visible:outline-none',
                        )}
                    />
                ))}
            </div>

            <AuthInputError message={error} />
        </div>
    );
}
