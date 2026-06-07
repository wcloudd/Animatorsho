import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ConsultationFormOption } from '@/lib/consultation-form-data';
import {
    CONSULTATION_INTEREST_OPTIONS,
    CONSULTATION_LEVEL_OPTIONS,
} from '@/lib/consultation-form-data';
import { cn } from '@/lib/utils';
import consultation from '@/routes/consultation';

const fieldClassName =
    'border-input bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

const textareaClassName = cn(fieldClassName, 'min-h-[100px]');

const selectTriggerClassName = cn(
    'h-10 w-full border-border bg-surface text-text shadow-xs',
    'dark:border-border dark:bg-surface dark:text-text dark:hover:bg-surface',
);

const selectContentClassName = cn(
    'border-border bg-surface text-text',
    'dark:border-border dark:bg-surface dark:text-text',
);

const selectItemClassName = cn(
    'text-text focus:bg-purple-soft focus:text-text',
    'dark:text-text dark:focus:bg-purple-soft',
);

type ConsultationSelectFieldProps = {
    id: string;
    label: string;
    value: string;
    options: ConsultationFormOption[];
    onValueChange: (value: string) => void;
    error?: string;
};

function ConsultationSelectField({
    id,
    label,
    value,
    options,
    onValueChange,
    error,
}: ConsultationSelectFieldProps) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Select value={value} onValueChange={onValueChange}>
                <SelectTrigger id={id} className={selectTriggerClassName}>
                    <SelectValue placeholder={`انتخاب ${label}`} />
                </SelectTrigger>
                <SelectContent
                    className={selectContentClassName}
                    position="popper"
                >
                    {options.map((option) => (
                        <SelectItem
                            key={option.value}
                            value={option.value}
                            className={selectItemClassName}
                        >
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <InputError message={error} />
        </div>
    );
}

export function ConsultationForm() {
    const [level, setLevel] = useState(
        CONSULTATION_LEVEL_OPTIONS[0]?.value ?? '',
    );
    const [interest, setInterest] = useState(
        CONSULTATION_INTEREST_OPTIONS[0]?.value ?? '',
    );

    const { data, setData, post, processing, errors, reset } = useForm({
        full_name: '',
        mobile: '',
        age: '',
        level: CONSULTATION_LEVEL_OPTIONS[0]?.value ?? '',
        interest: CONSULTATION_INTEREST_OPTIONS[0]?.value ?? '',
        note: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(consultation.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setLevel(CONSULTATION_LEVEL_OPTIONS[0]?.value ?? '');
                setInterest(CONSULTATION_INTEREST_OPTIONS[0]?.value ?? '');
            },
        });
    };

    return (
        <section
            id="consultation-form"
            className="scroll-mt-24 flex w-full flex-col gap-4"
            aria-labelledby="consultation-form-heading"
        >
            <h2
                id="consultation-form-heading"
                className="text-base font-bold text-text"
            >
                فرم درخواست مشاوره
            </h2>

            <form
                onSubmit={submit}
                className="flex flex-col gap-4 rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border"
            >
                <div className="grid gap-2">
                    <Label htmlFor="consultation-full-name">
                        نام و نام خانوادگی
                    </Label>
                    <Input
                        id="consultation-full-name"
                        name="full_name"
                        type="text"
                        autoComplete="name"
                        value={data.full_name}
                        onChange={(event) =>
                            setData('full_name', event.target.value)
                        }
                        className="bg-surface text-text text-start"
                        required
                    />
                    <InputError message={errors.name ?? errors.full_name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="consultation-mobile">شماره موبایل</Label>
                    <Input
                        id="consultation-mobile"
                        name="mobile"
                        type="tel"
                        inputMode="tel"
                        dir="ltr"
                        value={data.mobile}
                        onChange={(event) =>
                            setData('mobile', event.target.value)
                        }
                        className="bg-surface text-text text-start"
                        required
                    />
                    <InputError message={errors.mobile} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="consultation-age">سن</Label>
                    <Input
                        id="consultation-age"
                        name="age"
                        type="text"
                        inputMode="numeric"
                        value={data.age}
                        onChange={(event) => setData('age', event.target.value)}
                        className="bg-surface text-text text-start"
                    />
                    <InputError message={errors.age} />
                </div>

                <ConsultationSelectField
                    id="consultation-level"
                    label="سطح فعلی"
                    value={level}
                    options={CONSULTATION_LEVEL_OPTIONS}
                    onValueChange={(value) => {
                        setLevel(value);
                        setData('level', value);
                    }}
                    error={errors.level}
                />

                <ConsultationSelectField
                    id="consultation-interest"
                    label="علاقه‌مند به"
                    value={interest}
                    options={CONSULTATION_INTEREST_OPTIONS}
                    onValueChange={(value) => {
                        setInterest(value);
                        setData('interest', value);
                    }}
                    error={errors.interest}
                />

                <div className="grid gap-2">
                    <Label htmlFor="consultation-note">توضیح کوتاه</Label>
                    <textarea
                        id="consultation-note"
                        name="note"
                        rows={4}
                        value={data.note}
                        onChange={(event) => setData('note', event.target.value)}
                        className={textareaClassName}
                    />
                    <InputError message={errors.note} />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="btn-cta-green flex h-12 w-full items-center justify-center rounded-pill text-sm font-bold text-white disabled:opacity-70"
                >
                    {processing ? 'در حال ارسال...' : 'ارسال درخواست مشاوره'}
                </button>
            </form>
        </section>
    );
}
