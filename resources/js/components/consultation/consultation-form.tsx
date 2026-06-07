import { Link, useForm, usePage } from '@inertiajs/react';
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
    CONSULTATION_GUEST_CTA,
    CONSULTATION_INTEREST_OPTIONS,
    CONSULTATION_LEVEL_OPTIONS,
    CONSULTATION_VERIFIED_MOBILE_COPY,
    CONSULTATION_VERIFY_MOBILE_CTA,
} from '@/lib/consultation-form-data';
import { cn } from '@/lib/utils';
import { login, register } from '@/routes';
import consultation from '@/routes/consultation';
import { create as profileMobileCreate } from '@/routes/profile/mobile';

const fieldClassName =
    'border-input bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

const textareaClassName = cn(fieldClassName, 'min-h-[100px]');

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border';

const ctaCardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-gold-soft px-5 py-6 shadow-soft ring-1 ring-border';

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

function ConsultationGuestCta({ redirectQuery }: { redirectQuery: { redirect: string } }) {
    const copy = CONSULTATION_GUEST_CTA;

    return (
        <div
            className={ctaCardClassName}
            data-test="consultation-guest-cta"
        >
            <p className="text-center text-sm font-medium leading-relaxed text-text">
                {copy.message}
            </p>
            <div className="flex gap-3">
                <Link
                    href={login({ query: redirectQuery })}
                    className="flex h-11 flex-1 items-center justify-center rounded-pill bg-surface text-sm font-bold text-text shadow-soft ring-1 ring-border transition-colors hover:bg-purple-soft"
                >
                    {copy.loginLabel}
                </Link>
                <Link
                    href={register({ query: redirectQuery })}
                    className="flex h-11 flex-1 items-center justify-center rounded-pill bg-green text-sm font-bold text-white shadow-soft transition-opacity hover:opacity-95"
                >
                    {copy.registerLabel}
                </Link>
            </div>
        </div>
    );
}

function ConsultationVerifyMobileCta({
    redirectQuery,
}: {
    redirectQuery: { redirect: string };
}) {
    const copy = CONSULTATION_VERIFY_MOBILE_CTA;

    return (
        <div
            className={ctaCardClassName}
            data-test="consultation-verify-mobile-cta"
        >
            <p className="text-center text-sm font-medium leading-relaxed text-text">
                {copy.message}
            </p>
            <Link
                href={profileMobileCreate({ query: redirectQuery })}
                className="flex h-11 w-full items-center justify-center rounded-pill bg-green text-sm font-bold text-white shadow-soft transition-opacity hover:opacity-95"
            >
                {copy.ctaLabel}
            </Link>
        </div>
    );
}

type ConsultationSubmitFormProps = {
    verifiedMobile: string;
};

function ConsultationSubmitForm({ verifiedMobile }: ConsultationSubmitFormProps) {
    const [level, setLevel] = useState(
        CONSULTATION_LEVEL_OPTIONS[0]?.value ?? '',
    );
    const [interest, setInterest] = useState(
        CONSULTATION_INTEREST_OPTIONS[0]?.value ?? '',
    );

    const { data, setData, post, processing, errors, reset } = useForm({
        full_name: '',
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
        <form
            onSubmit={submit}
            className={cardClassName}
            data-test="consultation-submit-form"
        >
            <p className="text-center text-sm font-medium leading-relaxed text-muted">
                {CONSULTATION_VERIFIED_MOBILE_COPY.snapshotNote}
            </p>

            <div
                className="grid gap-2"
                data-test="consultation-verified-mobile-display"
            >
                <Label htmlFor="consultation-verified-mobile">
                    {CONSULTATION_VERIFIED_MOBILE_COPY.mobileLabel}
                </Label>
                <Input
                    id="consultation-verified-mobile"
                    type="tel"
                    value={verifiedMobile}
                    readOnly
                    disabled
                    dir="ltr"
                    className="bg-surface text-text text-start"
                />
            </div>

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
    );
}

function userHasVerifiedMobile(user: {
    mobile?: unknown;
    mobile_verified_at?: unknown;
}): boolean {
    return (
        typeof user.mobile === 'string' &&
        user.mobile !== '' &&
        user.mobile_verified_at !== null &&
        user.mobile_verified_at !== undefined
    );
}

export function ConsultationForm() {
    const { auth, url } = usePage().props;
    const user = auth.user;
    const redirectQuery = { redirect: url };

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

            {user === null ? (
                <ConsultationGuestCta redirectQuery={redirectQuery} />
            ) : userHasVerifiedMobile(user) ? (
                <ConsultationSubmitForm
                    verifiedMobile={user.mobile as string}
                />
            ) : (
                <ConsultationVerifyMobileCta redirectQuery={redirectQuery} />
            )}
        </section>
    );
}
