import type { ComponentProps } from 'react';
import {
    dateObjectToGregorianIso,
    gregorianIsoToDateObject,
} from '@/lib/jalali-date';
import DatePicker from '@/lib/react-multi-date-picker';
import { cn } from '@/lib/utils';
import persian from 'react-date-object/calendars/persian';
import persian_fa from 'react-date-object/locales/persian_fa';
import 'react-multi-date-picker/styles/layouts/mobile.css';
import '../../../css/admin-jalali-date-picker.css';

const adminDateInputClassName =
    'h-10 w-full min-w-0 rounded-xl border border-[#e8e0f0] bg-surface px-3 text-sm text-text shadow-xs outline-none placeholder:text-muted focus-visible:ring-[3px] focus-visible:ring-purple/30';

type AdminJalaliDateInputProps = {
    value: string;
    onChange: (value: string) => void;
    label?: string;
    placeholder?: string;
    id?: string;
    className?: string;
    showClear?: boolean;
    clearLabel?: string;
    disabled?: boolean;
    inputClassName?: string;
};

export function AdminJalaliDateInput({
    value,
    onChange,
    label,
    placeholder = 'انتخاب تاریخ',
    id,
    className,
    showClear = true,
    clearLabel = 'پاک کردن',
    disabled = false,
    inputClassName,
}: AdminJalaliDateInputProps) {
    const handleChange: ComponentProps<typeof DatePicker>['onChange'] = (
        date,
    ) => {
        const iso = dateObjectToGregorianIso(date);
        onChange(iso ?? '');
    };

    const handleClear = () => {
        onChange('');
    };

    return (
        <div
            className={cn(
                'admin-jalali-date-picker flex min-w-0 flex-col gap-1',
                className,
            )}
            dir="rtl"
        >
            {label ? (
                <label htmlFor={id} className="text-xs font-medium text-muted">
                    {label}
                </label>
            ) : null}
            <div className="flex min-w-0 items-center gap-1">
                <DatePicker
                    id={id}
                    value={gregorianIsoToDateObject(value)}
                    onChange={handleChange}
                    calendar={persian}
                    locale={persian_fa}
                    format="YYYY/MM/DD"
                    placeholder={placeholder}
                    disabled={disabled}
                    containerClassName="w-full min-w-0"
                    inputClass={cn(adminDateInputClassName, inputClassName)}
                    calendarPosition="bottom-right"
                    arrow={false}
                    editable={false}
                />
                {showClear && value !== '' ? (
                    <button
                        type="button"
                        onClick={handleClear}
                        className="shrink-0 rounded-lg px-2 py-1 text-xs font-medium text-muted transition hover:bg-purple-soft hover:text-purple"
                        aria-label={clearLabel}
                    >
                        {clearLabel}
                    </button>
                ) : null}
            </div>
        </div>
    );
}
