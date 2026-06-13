import { AdminJalaliDateInput } from '@/components/admin/admin-jalali-date-input';
import { cn } from '@/lib/utils';

type AdminJalaliDateRangeProps = {
    fromValue: string;
    toValue: string;
    onFromChange: (value: string) => void;
    onToChange: (value: string) => void;
    className?: string;
    fromLabel?: string;
    toLabel?: string;
    placeholder?: string;
    clearLabel?: string;
};

export function AdminJalaliDateRange({
    fromValue,
    toValue,
    onFromChange,
    onToChange,
    className,
    fromLabel = 'از تاریخ',
    toLabel = 'تا تاریخ',
    placeholder = 'انتخاب تاریخ',
    clearLabel = 'پاک کردن',
}: AdminJalaliDateRangeProps) {
    return (
        <div
            className={cn(
                'grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-2',
                className,
            )}
        >
            <AdminJalaliDateInput
                id="admin-jalali-date-from"
                value={fromValue}
                onChange={onFromChange}
                label={fromLabel}
                placeholder={placeholder}
                clearLabel={clearLabel}
            />
            <AdminJalaliDateInput
                id="admin-jalali-date-to"
                value={toValue}
                onChange={onToChange}
                label={toLabel}
                placeholder={placeholder}
                clearLabel={clearLabel}
            />
        </div>
    );
}
