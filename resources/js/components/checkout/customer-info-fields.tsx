import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const fieldClassName =
    'border-border bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 dark:border-border dark:bg-surface dark:text-text';

type CustomerInfoFieldsProps = {
    data: Record<string, string>;
    setData: (key: string, value: string) => void;
    errors: Partial<Record<string, string>>;
    className?: string;
};

export function CustomerInfoFields({
    data,
    setData,
    errors,
    className,
}: CustomerInfoFieldsProps) {
    return (
        <section
            className={cn(
                'flex w-full flex-col gap-4 rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border',
                className,
            )}
            aria-labelledby="customer-info-heading"
        >
            <h2
                id="customer-info-heading"
                className="text-center text-base font-bold text-text"
            >
                اطلاعات تماس
            </h2>
            <p className="text-center text-sm font-medium leading-relaxed text-muted">
                برای پیگیری سفارش، لایسنس SpotPlayer و پشتیبانی به نام و شماره
                موبایل نیاز داریم.
            </p>

            <div className="grid gap-2">
                <Label htmlFor="checkout-customer-name">
                    نام و نام خانوادگی
                </Label>
                <Input
                    id="checkout-customer-name"
                    name="customer_name"
                    type="text"
                    required
                    autoComplete="name"
                    value={data.customer_name ?? ''}
                    onChange={(event) =>
                        setData('customer_name', event.target.value)
                    }
                    className={cn(fieldClassName, 'h-10')}
                />
                {errors.customer_name ? (
                    <p className="text-sm text-red">{errors.customer_name}</p>
                ) : null}
            </div>

            <div className="grid gap-2">
                <Label htmlFor="checkout-customer-mobile">شماره موبایل</Label>
                <Input
                    id="checkout-customer-mobile"
                    name="customer_mobile"
                    type="tel"
                    required
                    inputMode="tel"
                    dir="ltr"
                    placeholder="09123456789"
                    autoComplete="tel"
                    value={data.customer_mobile ?? ''}
                    onChange={(event) =>
                        setData('customer_mobile', event.target.value)
                    }
                    className={cn(fieldClassName, 'h-10')}
                />
                {errors.customer_mobile ? (
                    <p className="text-sm text-red">{errors.customer_mobile}</p>
                ) : null}
            </div>
        </section>
    );
}
