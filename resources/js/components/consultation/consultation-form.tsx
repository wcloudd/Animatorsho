import { FormEvent, useState } from 'react';
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
    name: string;
    value: string;
    options: ConsultationFormOption[];
    onValueChange: (value: string) => void;
};

function ConsultationSelectField({
    id,
    label,
    name,
    value,
    options,
    onValueChange,
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
            <input type="hidden" name={name} value={value} />
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

    // Backend TODO: add server-side validation, rate limiting, honeypot field,
    // and optional spam detection when wiring up real submission.
    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
    }

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
                onSubmit={handleSubmit}
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
                        className="bg-surface text-text text-start"
                    />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="consultation-mobile">شماره موبایل</Label>
                    <Input
                        id="consultation-mobile"
                        name="mobile"
                        type="tel"
                        inputMode="tel"
                        dir="ltr"
                        className="bg-surface text-text text-start"
                    />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="consultation-age">سن</Label>
                    <Input
                        id="consultation-age"
                        name="age"
                        type="text"
                        inputMode="numeric"
                        className="bg-surface text-text text-start"
                    />
                </div>

                <ConsultationSelectField
                    id="consultation-level"
                    label="سطح فعلی"
                    name="level"
                    value={level}
                    options={CONSULTATION_LEVEL_OPTIONS}
                    onValueChange={setLevel}
                />

                <ConsultationSelectField
                    id="consultation-interest"
                    label="علاقه‌مند به"
                    name="interest"
                    value={interest}
                    options={CONSULTATION_INTEREST_OPTIONS}
                    onValueChange={setInterest}
                />

                <div className="grid gap-2">
                    <Label htmlFor="consultation-note">توضیح کوتاه</Label>
                    <textarea
                        id="consultation-note"
                        name="note"
                        rows={4}
                        className={textareaClassName}
                    />
                </div>

                <button
                    type="submit"
                    className="btn-cta-green flex h-12 w-full items-center justify-center rounded-pill text-sm font-bold text-white"
                >
                    ارسال درخواست مشاوره
                </button>
            </form>
        </section>
    );
}
