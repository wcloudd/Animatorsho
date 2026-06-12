import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCallout } from '@/components/admin/admin-callout';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import InputError from '@/components/input-error';
import { surfaceCardClassName } from '@/components/page-container';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatAdminDate } from '@/lib/format-admin-date';
import { cn } from '@/lib/utils';
import {
    lookup as manualEnrollmentLookup,
    store as manualEnrollmentStore,
    userSuggestions as manualEnrollmentUserSuggestions,
} from '@/routes/admin/manual-enrollments';
import { update as updateManagedUser } from '@/routes/admin/manual-enrollments/users';
import type { AdminStatusOption } from '@/types/admin';

type LookupPreviewStatus =
    | 'empty'
    | 'found'
    | 'not_found'
    | 'needs_mobile'
    | 'invalid';

type LookupPreviewUser = {
    id: number;
    name: string;
    username: string | null;
    mobile: string | null;
    hasMobile: boolean;
    mobileVerified: boolean;
};

type UserEditForm = {
    name: string;
    username: string;
    mobile: string;
    verify_mobile: boolean;
};

type LookupPreview = {
    status: LookupPreviewStatus;
    message: string | null;
    user: LookupPreviewUser | null;
};

type UserSuggestion = {
    id: number;
    name: string;
    username: string | null;
    mobile: string | null;
    hasMobile: boolean;
    mobileVerified: boolean;
    label: string;
};

type UserSuggestionsResponse = {
    suggestions: UserSuggestion[];
};

type UserFlowMode =
    | 'search_only'
    | 'existing_found'
    | 'existing_needs_mobile'
    | 'new_buyer'
    | 'invalid';

const MOBILE_REQUIRED_MESSAGE =
    'این کاربر شماره موبایل ثبت‌شده ندارد. برای ثبت دسترسی، شماره موبایل را وارد کنید.';

const SUGGESTIONS_DEBOUNCE_MS = 300;

function readXsrfToken(): string {
    const token = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return token ? decodeURIComponent(token) : '';
}

const lookupPreviewStyles: Record<
    LookupPreviewStatus,
    { box: string; title: string }
> = {
    empty: {
        box: 'rounded-xl bg-purple-soft/50 px-3 py-2 ring-1 ring-purple/10',
        title: 'text-xs font-medium text-muted',
    },
    found: {
        box: 'rounded-xl bg-green-soft px-3 py-2 ring-1 ring-green/15',
        title: 'text-xs font-medium text-green',
    },
    not_found: {
        box: 'rounded-xl bg-gold-soft px-3 py-2 ring-1 ring-gold/15',
        title: 'text-xs font-medium text-gold',
    },
    needs_mobile: {
        box: 'rounded-xl bg-gold-soft px-3 py-2 ring-1 ring-gold/15',
        title: 'text-xs font-medium text-gold',
    },
    invalid: {
        box: 'rounded-xl bg-red/10 px-3 py-2 ring-1 ring-red/15',
        title: 'text-xs font-medium text-red/80',
    },
};

function isMobileLikeInput(value: string): boolean {
    return /^[\d+\-\s()]+$/.test(value) && /\d/.test(value);
}

function resolveUserFlowMode(
    lookupPreview: LookupPreview | null,
    lookupIsStale: boolean,
    userLookup: string,
    customerMobile: string,
    customerName: string,
): UserFlowMode {
    if (lookupPreview && !lookupIsStale) {
        if (lookupPreview.status === 'found' && lookupPreview.user) {
            return 'existing_found';
        }

        if (lookupPreview.status === 'needs_mobile' && lookupPreview.user) {
            return 'existing_needs_mobile';
        }

        if (lookupPreview.status === 'not_found') {
            return 'new_buyer';
        }

        if (lookupPreview.status === 'invalid') {
            return 'invalid';
        }
    }

    if (userLookup.trim() === '') {
        if (customerMobile.trim() !== '' || customerName.trim() !== '') {
            return 'new_buyer';
        }

        return 'search_only';
    }

    return 'search_only';
}

function resolveSubmitReadiness(
    mode: UserFlowMode,
    userLookup: string,
    customerName: string,
    customerMobile: string,
    lookupPreview: LookupPreview | null,
    lookupIsStale: boolean,
): { ready: boolean; message: string | null } {
    if (mode === 'invalid') {
        return {
            ready: false,
            message: 'جستجوی کاربر نامعتبر است. مقدار را اصلاح کنید.',
        };
    }

    if (mode === 'search_only') {
        return {
            ready: false,
            message:
                'ابتدا کاربر را جستجو و بررسی کنید، یا اطلاعات کاربر جدید را در بخش دوم وارد کنید.',
        };
    }

    const trimmedLookup = userLookup.trim();

    if (trimmedLookup !== '') {
        if (lookupPreview === null) {
            return {
                ready: false,
                message: 'قبل از ثبت دسترسی، «بررسی کاربر» را بزنید.',
            };
        }

        if (lookupIsStale) {
            return {
                ready: false,
                message:
                    'نتیجه بررسی کاربر قدیمی است. دوباره «بررسی کاربر» را بزنید.',
            };
        }
    }

    if (mode === 'existing_needs_mobile' && customerMobile.trim() === '') {
        return {
            ready: false,
            message: 'برای این کاربر، شماره موبایل را وارد کنید.',
        };
    }

    if (mode === 'new_buyer') {
        if (customerName.trim() === '') {
            return {
                ready: false,
                message: 'نام کاربر جدید را وارد کنید.',
            };
        }

        const lookupIsMobile =
            trimmedLookup !== '' && isMobileLikeInput(trimmedLookup);

        if (customerMobile.trim() === '' && !lookupIsMobile) {
            return {
                ready: false,
                message: 'شماره موبایل کاربر جدید را وارد کنید.',
            };
        }
    }

    if (customerName.trim() === '') {
        return {
            ready: false,
            message: 'نام مشتری برای ثبت سفارش لازم است.',
        };
    }

    return { ready: true, message: null };
}

function LookupStatusCard({
    preview,
    stale,
}: {
    preview: LookupPreview;
    stale: boolean;
}) {
    if (preview.status === 'empty' && !stale) {
        return null;
    }

    const styles = lookupPreviewStyles[preview.status];

    return (
        <div className={cn(styles.box, 'flex flex-col gap-2')}>
            {stale ? (
                <p className="text-xs font-medium text-muted">
                    نتیجه بررسی قدیمی است؛ دوباره «بررسی کاربر» را بزنید.
                </p>
            ) : null}
            {preview.message ? (
                <p className={styles.title}>{preview.message}</p>
            ) : null}
        </div>
    );
}

function SelectedUserCard({
    user,
    confirmed,
    onClear,
}: {
    user: LookupPreviewUser;
    confirmed: boolean;
    onClear: () => void;
}) {
    return (
        <div
            className={cn(
                'rounded-2xl border p-4',
                confirmed
                    ? 'border-green/25 bg-green-soft/40 ring-1 ring-green/10'
                    : 'border-gold/25 bg-gold-soft/40 ring-1 ring-gold/10',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="flex flex-col gap-1">
                    <p className="text-xs font-medium text-muted">
                        {confirmed ? 'کاربر انتخاب‌شده' : 'کاربر یافت‌شده'}
                    </p>
                    <p className="text-base font-bold text-text">{user.name}</p>
                </div>
                <AdminButton
                    type="button"
                    adminVariant="outline"
                    className="shrink-0 text-xs"
                    onClick={onClear}
                >
                    تغییر کاربر
                </AdminButton>
            </div>

            <AdminInfoGrid className="mt-3">
                <AdminDetailRow
                    label="شناسه"
                    value={String(user.id)}
                />
                <AdminDetailRow
                    label="نام کاربری"
                    value={user.username ? `@${user.username}` : '—'}
                />
                <AdminDetailRow
                    label="موبایل"
                    value={user.mobile ?? '—'}
                />
                <AdminDetailRow
                    label="وضعیت موبایل"
                    value={user.hasMobile ? 'ثبت‌شده' : 'ثبت نشده'}
                />
                <AdminDetailRow
                    label="تأیید موبایل"
                    value={
                        user.mobileVerified
                            ? 'تأییدشده'
                            : user.hasMobile
                              ? 'تأیید نشده'
                              : '—'
                    }
                />
            </AdminInfoGrid>
        </div>
    );
}

type PackageOption = {
    id: number;
    title: string;
    slug: string;
    priceFormatted: string;
};

type RecentGrant = {
    id: number;
    orderNumber: string;
    customerName: string | null;
    customerMobile: string | null;
    packageTitle: string;
    sourceLabel: string | null;
    licenseStatus: string | null;
    createdAt: string | null;
};

type PageProps = {
    packages: PackageOption[];
    sourceOptions: AdminStatusOption[];
    recentGrants: RecentGrant[];
};

const textareaClassName = cn(
    'border-input bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
    'min-h-[80px]',
);

export default function AdminManualEnrollmentsIndex({
    packages,
    sourceOptions,
    recentGrants,
}: PageProps) {
    const defaultPackageId =
        packages.length > 0 ? String(packages[0].id) : '';

    const { data, setData, post, processing, errors, reset } = useForm({
        customer_name: '',
        user_lookup: '',
        customer_mobile: '',
        course_package_id: defaultPackageId,
        source: 'eitaa',
        admin_note: '',
        license_key: '',
    });

    const [lookupPreview, setLookupPreview] = useState<LookupPreview | null>(
        null,
    );
    const [lookupCheckedFor, setLookupCheckedFor] = useState('');
    const [lookupChecking, setLookupChecking] = useState(false);
    const [lookupError, setLookupError] = useState<string | null>(null);
    const [suggestions, setSuggestions] = useState<UserSuggestion[]>([]);
    const [suggestionsLoading, setSuggestionsLoading] = useState(false);
    const [suggestionsOpen, setSuggestionsOpen] = useState(false);
    const [submitHint, setSubmitHint] = useState<string | null>(null);
    const [userEditForm, setUserEditForm] = useState<UserEditForm>({
        name: '',
        username: '',
        mobile: '',
        verify_mobile: false,
    });
    const [userEditBaselineMobile, setUserEditBaselineMobile] = useState('');
    const [userEditSaving, setUserEditSaving] = useState(false);
    const [userEditErrors, setUserEditErrors] = useState<
        Record<string, string | string[]>
    >({});
    const [userEditSuccess, setUserEditSuccess] = useState<string | null>(
        null,
    );
    const lookupContainerRef = useRef<HTMLDivElement>(null);

    const lookupIsStale =
        lookupPreview !== null &&
        lookupCheckedFor !== data.user_lookup.trim();

    const flowMode = useMemo(
        () =>
            resolveUserFlowMode(
                lookupPreview,
                lookupIsStale,
                data.user_lookup,
                data.customer_mobile,
                data.customer_name,
            ),
        [
            lookupPreview,
            lookupIsStale,
            data.user_lookup,
            data.customer_mobile,
            data.customer_name,
        ],
    );

    const submitReadiness = useMemo(
        () =>
            resolveSubmitReadiness(
                flowMode,
                data.user_lookup,
                data.customer_name,
                data.customer_mobile,
                lookupPreview,
                lookupIsStale,
            ),
        [
            flowMode,
            data.user_lookup,
            data.customer_name,
            data.customer_mobile,
            lookupPreview,
            lookupIsStale,
        ],
    );

    const confirmedUser =
        lookupPreview?.user &&
        !lookupIsStale &&
        (flowMode === 'existing_found' ||
            flowMode === 'existing_needs_mobile')
            ? lookupPreview.user
            : null;

    const syncUserIdentityFields = (user: LookupPreviewUser) => {
        setData('customer_name', user.name);

        if (user.mobile) {
            setData('customer_mobile', user.mobile);
        }
    };

    const applyUserSummary = (user: LookupPreviewUser) => {
        const nextStatus = user.hasMobile ? 'found' : 'needs_mobile';
        const lookupValue = user.username ?? user.mobile ?? data.user_lookup;

        setLookupPreview({
            status: nextStatus,
            message: user.hasMobile
                ? 'کاربر پیدا شد'
                : MOBILE_REQUIRED_MESSAGE,
            user,
        });
        setData('customer_name', user.name);
        setData('customer_mobile', user.mobile ?? '');
        setData('user_lookup', lookupValue);
        setLookupCheckedFor(lookupValue.trim());
        setUserEditForm({
            name: user.name,
            username: user.username ?? '',
            mobile: user.mobile ?? '',
            verify_mobile: user.mobileVerified,
        });
        setUserEditBaselineMobile(user.mobile ?? '');
    };

    useEffect(() => {
        if (!confirmedUser) {
            return;
        }

        setUserEditForm({
            name: confirmedUser.name,
            username: confirmedUser.username ?? '',
            mobile: confirmedUser.mobile ?? data.customer_mobile,
            verify_mobile: confirmedUser.mobileVerified,
        });
        setUserEditBaselineMobile(confirmedUser.mobile ?? '');
        setUserEditErrors({});
        setUserEditSuccess(null);
    }, [confirmedUser?.id, lookupCheckedFor]);

    const saveUserInfo = async () => {
        if (!confirmedUser) {
            return;
        }

        setUserEditSaving(true);
        setUserEditErrors({});
        setUserEditSuccess(null);

        try {
            const response = await fetch(
                updateManagedUser.url(confirmedUser.id),
                {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': readXsrfToken(),
                    },
                    body: JSON.stringify({
                        name: userEditForm.name,
                        username: userEditForm.username,
                        mobile: userEditForm.mobile,
                        verify_mobile: userEditForm.verify_mobile,
                    }),
                },
            );

            if (response.status === 422) {
                const payload = (await response.json()) as {
                    errors?: Record<string, string | string[]>;
                };

                setUserEditErrors(payload.errors ?? {});

                return;
            }

            if (!response.ok) {
                throw new Error('save_failed');
            }

            const payload = (await response.json()) as {
                message: string;
                user: LookupPreviewUser;
            };

            applyUserSummary(payload.user);
            setUserEditSuccess(payload.message);
        } catch {
            setUserEditErrors({
                general: 'ذخیره اطلاعات انجام نشد. دوباره تلاش کنید.',
            });
        } finally {
            setUserEditSaving(false);
        }
    };

    const userEditFieldError = (field: string): string | undefined => {
        const error = userEditErrors[field];

        if (Array.isArray(error)) {
            return error[0];
        }

        return error;
    };

    const clearSelectedUser = () => {
        reset('customer_name', 'user_lookup', 'customer_mobile');
        setLookupPreview(null);
        setLookupCheckedFor('');
        setLookupError(null);
        setSuggestions([]);
        setSuggestionsOpen(false);
        setSubmitHint(null);
        setUserEditErrors({});
        setUserEditSuccess(null);
        setUserEditForm({
            name: '',
            username: '',
            mobile: '',
            verify_mobile: false,
        });
        setUserEditBaselineMobile('');
    };

    useEffect(() => {
        const query = data.user_lookup.trim();

        if (query === '') {
            setSuggestions([]);
            setSuggestionsLoading(false);
            setSuggestionsOpen(false);

            return;
        }

        const isMobileLike = isMobileLikeInput(query);
        const digitCount = query.replace(/\D/g, '').length;

        if (isMobileLike && digitCount < 4) {
            setSuggestions([]);
            setSuggestionsOpen(false);

            return;
        }

        if (!isMobileLike && query.length < 2) {
            setSuggestions([]);
            setSuggestionsOpen(false);

            return;
        }

        const controller = new AbortController();
        const timeoutId = window.setTimeout(() => {
            setSuggestionsLoading(true);

            void (async () => {
                try {
                    const response = await fetch(
                        manualEnrollmentUserSuggestions.url({
                            query: { q: query },
                        }),
                        {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            signal: controller.signal,
                        },
                    );

                    if (!response.ok) {
                        throw new Error('suggestions_failed');
                    }

                    const payload =
                        (await response.json()) as UserSuggestionsResponse;

                    setSuggestions(payload.suggestions);
                    setSuggestionsOpen(payload.suggestions.length > 0);
                } catch (error) {
                    if (
                        error instanceof DOMException &&
                        error.name === 'AbortError'
                    ) {
                        return;
                    }

                    setSuggestions([]);
                    setSuggestionsOpen(false);
                } finally {
                    if (!controller.signal.aborted) {
                        setSuggestionsLoading(false);
                    }
                }
            })();
        }, SUGGESTIONS_DEBOUNCE_MS);

        return () => {
            window.clearTimeout(timeoutId);
            controller.abort();
        };
    }, [data.user_lookup]);

    useEffect(() => {
        const handlePointerDown = (event: MouseEvent) => {
            if (
                lookupContainerRef.current &&
                !lookupContainerRef.current.contains(event.target as Node)
            ) {
                setSuggestionsOpen(false);
            }
        };

        document.addEventListener('mousedown', handlePointerDown);

        return () => {
            document.removeEventListener('mousedown', handlePointerDown);
        };
    }, []);

    const previewFromSuggestion = (
        suggestion: UserSuggestion,
    ): LookupPreview => ({
        status: suggestion.hasMobile ? 'found' : 'needs_mobile',
        message: suggestion.hasMobile
            ? 'کاربر پیدا شد'
            : MOBILE_REQUIRED_MESSAGE,
        user: {
            id: suggestion.id,
            name: suggestion.name,
            username: suggestion.username,
            mobile: suggestion.mobile,
            hasMobile: suggestion.hasMobile,
            mobileVerified: suggestion.mobileVerified,
        },
    });

    const selectSuggestion = (suggestion: UserSuggestion) => {
        const lookupValue = suggestion.username ?? suggestion.mobile ?? '';

        setData('user_lookup', lookupValue);
        syncUserIdentityFields({
            id: suggestion.id,
            name: suggestion.name,
            username: suggestion.username,
            mobile: suggestion.mobile,
            hasMobile: suggestion.hasMobile,
            mobileVerified: suggestion.mobileVerified,
        });
        setLookupPreview(previewFromSuggestion(suggestion));
        setLookupCheckedFor(lookupValue);
        setLookupError(null);
        setSuggestions([]);
        setSuggestionsOpen(false);
        setSubmitHint(null);
    };

    const checkUser = async () => {
        const lookup = data.user_lookup.trim();

        setLookupChecking(true);
        setLookupError(null);

        try {
            const query: Record<string, string> = {};

            if (lookup !== '') {
                query.user_lookup = lookup;
            }

            if (data.customer_mobile.trim() !== '') {
                query.customer_mobile = data.customer_mobile.trim();
            }

            const response = await fetch(
                manualEnrollmentLookup.url({ query }),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            if (!response.ok) {
                throw new Error('lookup_failed');
            }

            const preview = (await response.json()) as LookupPreview;

            setLookupPreview(preview);
            setLookupCheckedFor(lookup);

            if (preview.user) {
                syncUserIdentityFields(preview.user);
            } else if (
                preview.status === 'not_found' &&
                lookup !== '' &&
                isMobileLikeInput(lookup)
            ) {
                setData('customer_mobile', lookup);
            }

            setSubmitHint(null);
        } catch {
            setLookupError('بررسی کاربر انجام نشد. دوباره تلاش کنید.');
            setLookupPreview(null);
            setLookupCheckedFor('');
        } finally {
            setLookupChecking(false);
        }
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!submitReadiness.ready) {
            setSubmitHint(submitReadiness.message);

            return;
        }

        setSubmitHint(null);

        post(manualEnrollmentStore.url(), {
            preserveScroll: true,
            onSuccess: () => {
                reset(
                    'customer_name',
                    'user_lookup',
                    'customer_mobile',
                    'admin_note',
                    'license_key',
                );
                setLookupPreview(null);
                setLookupCheckedFor('');
                setLookupError(null);
                setSuggestions([]);
                setSuggestionsOpen(false);
                setSubmitHint(null);
            },
        });
    };

    return (
        <>
            <Head title="کاربران و دسترسی‌ها" />
            <AdminPageHeader
                title="کاربران و دسترسی‌ها"
                description="جستجو، بررسی کاربر، ثبت دسترسی دوره"
            />

            <div className="flex flex-col gap-5">
                <div className="rounded-xl bg-purple-soft/50 px-4 py-3 text-sm leading-relaxed text-text ring-1 ring-purple/10">
                    کاربر موجود را با موبایل یا نام کاربری پیدا کنید. برای
                    خریدار خارج از سایت بدون حساب، اطلاعات «کاربر جدید» را
                    وارد کنید. با کلید لایسنس یا API، دسترسی فعال می‌شود؛ در
                    غیر این صورت از{' '}
                    <a
                        href="/admin/licenses"
                        className="font-bold text-purple underline-offset-2 hover:underline"
                    >
                        لایسنس‌ها
                    </a>{' '}
                    فعال کنید.
                </div>

                <form
                    onSubmit={submit}
                    className="flex flex-col gap-5"
                >
                    <section
                        className={cn(
                            surfaceCardClassName,
                            'flex flex-col gap-4 p-4 sm:p-5',
                        )}
                    >
                        <AdminSectionTitle>
                            ۱. جستجو و انتخاب کاربر
                        </AdminSectionTitle>

                        <div className="grid gap-2">
                            <Label htmlFor="user_lookup">
                                موبایل یا نام کاربری
                            </Label>
                            <div
                                ref={lookupContainerRef}
                                className="relative flex flex-col gap-2 sm:flex-row"
                            >
                                <div className="relative sm:flex-1">
                                    <Input
                                        id="user_lookup"
                                        value={data.user_lookup}
                                        onChange={(event) => {
                                            const value = event.target.value;

                                            setData('user_lookup', value);
                                            setLookupError(null);
                                            setSubmitHint(null);

                                            if (value.trim() === '') {
                                                setLookupPreview(null);
                                                setLookupCheckedFor('');
                                            }
                                        }}
                                        onFocus={() => {
                                            if (suggestions.length > 0) {
                                                setSuggestionsOpen(true);
                                            }
                                        }}
                                        dir="ltr"
                                        placeholder="09123456789 یا username"
                                        autoComplete="off"
                                        aria-autocomplete="list"
                                        aria-expanded={suggestionsOpen}
                                        aria-controls="user_lookup_suggestions"
                                    />
                                    {suggestionsOpen ? (
                                        <div
                                            id="user_lookup_suggestions"
                                            className="absolute inset-x-0 top-full z-20 mt-1 overflow-hidden rounded-xl border border-[#e8e0f0] bg-surface shadow-sm"
                                        >
                                            <p className="border-b border-[#e8e0f0] px-3 py-2 text-xs font-medium text-muted">
                                                منظورت این کاربره؟
                                            </p>
                                            <ul className="max-h-56 overflow-y-auto">
                                                {suggestions.map(
                                                    (suggestion) => (
                                                        <li
                                                            key={suggestion.id}
                                                        >
                                                            <button
                                                                type="button"
                                                                className="flex w-full flex-col gap-0.5 px-3 py-2 text-start transition hover:bg-purple-soft/60"
                                                                onClick={() =>
                                                                    selectSuggestion(
                                                                        suggestion,
                                                                    )
                                                                }
                                                            >
                                                                <span className="text-sm font-medium text-text">
                                                                    {
                                                                        suggestion.name
                                                                    }
                                                                </span>
                                                                <span
                                                                    className="text-xs text-muted"
                                                                    dir="ltr"
                                                                >
                                                                    {[
                                                                        suggestion.username
                                                                            ? `@${suggestion.username}`
                                                                            : null,
                                                                        suggestion.mobile,
                                                                    ]
                                                                        .filter(
                                                                            Boolean,
                                                                        )
                                                                        .join(
                                                                            ' · ',
                                                                        )}
                                                                </span>
                                                            </button>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </div>
                                    ) : null}
                                </div>
                                <AdminButton
                                    type="button"
                                    adminVariant="outline"
                                    disabled={
                                        lookupChecking ||
                                        data.user_lookup.trim() === ''
                                    }
                                    onClick={() => void checkUser()}
                                    className="shrink-0"
                                >
                                    {lookupChecking
                                        ? 'در حال بررسی...'
                                        : 'بررسی کاربر'}
                                </AdminButton>
                            </div>
                            {suggestionsLoading ? (
                                <p className="text-xs text-muted">
                                    در حال جستجو...
                                </p>
                            ) : null}
                            {lookupError ? (
                                <p className="text-xs text-red">{lookupError}</p>
                            ) : null}
                            {lookupPreview ? (
                                <LookupStatusCard
                                    preview={lookupPreview}
                                    stale={lookupIsStale}
                                />
                            ) : null}
                            {confirmedUser ? (
                                <SelectedUserCard
                                    user={confirmedUser}
                                    confirmed={flowMode === 'existing_found'}
                                    onClear={clearSelectedUser}
                                />
                            ) : null}
                            <InputError message={errors.user_lookup} />
                        </div>
                    </section>

                    <section
                        className={cn(
                            surfaceCardClassName,
                            'flex flex-col gap-4 p-4 sm:p-5',
                        )}
                    >
                        <AdminSectionTitle>۲. اطلاعات کاربر</AdminSectionTitle>

                        {flowMode === 'search_only' ? (
                            <AdminEmptyState message="کاربری انتخاب نشده است. در بخش اول جستجو کنید، یا برای کاربر جدید نام و موبایل را پایین وارد کنید." />
                        ) : null}

                        {flowMode === 'invalid' ? (
                            <AdminCallout variant="warning">
                                جستجوی کاربر نامعتبر است. مقدار را اصلاح کرده
                                و دوباره بررسی کنید.
                            </AdminCallout>
                        ) : null}

                        {(flowMode === 'existing_found' ||
                            flowMode === 'existing_needs_mobile') &&
                        confirmedUser ? (
                            <div className="flex flex-col gap-4">
                                {flowMode === 'existing_needs_mobile' ? (
                                    <AdminCallout variant="warning">
                                        {MOBILE_REQUIRED_MESSAGE}
                                    </AdminCallout>
                                ) : null}

                                <div className="grid gap-2">
                                    <Label htmlFor="managed_user_name">نام</Label>
                                    <Input
                                        id="managed_user_name"
                                        value={userEditForm.name}
                                        onChange={(event) => {
                                            const value = event.target.value;

                                            setUserEditForm((current) => ({
                                                ...current,
                                                name: value,
                                            }));
                                            setData('customer_name', value);
                                        }}
                                        autoComplete="name"
                                    />
                                    <InputError
                                        message={userEditFieldError('name')}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="managed_user_username">
                                        نام کاربری
                                    </Label>
                                    <Input
                                        id="managed_user_username"
                                        value={userEditForm.username}
                                        onChange={(event) => {
                                            const value =
                                                event.target.value.toLowerCase();

                                            setUserEditForm((current) => ({
                                                ...current,
                                                username: value,
                                            }));
                                        }}
                                        dir="ltr"
                                        placeholder="username"
                                        autoComplete="off"
                                    />
                                    <InputError
                                        message={userEditFieldError('username')}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="managed_user_mobile">
                                        شماره موبایل
                                        {flowMode === 'existing_needs_mobile'
                                            ? ' (الزامی)'
                                            : ''}
                                    </Label>
                                    <Input
                                        id="managed_user_mobile"
                                        value={userEditForm.mobile}
                                        onChange={(event) => {
                                            const value = event.target.value;
                                            const shouldVerify =
                                                value.trim() !== '' &&
                                                value !== userEditBaselineMobile;

                                            setUserEditForm((current) => ({
                                                ...current,
                                                mobile: value,
                                                verify_mobile: shouldVerify
                                                    ? true
                                                    : current.verify_mobile,
                                            }));
                                            setData('customer_mobile', value);
                                        }}
                                        dir="ltr"
                                        inputMode="tel"
                                        autoComplete="tel"
                                        placeholder="09123456789"
                                    />
                                    <InputError
                                        message={
                                            userEditFieldError('mobile') ??
                                            errors.customer_mobile
                                        }
                                    />
                                </div>

                                <div className="flex items-start gap-3 rounded-xl bg-purple-soft/40 px-3 py-3">
                                    <Checkbox
                                        id="managed_user_verify_mobile"
                                        checked={userEditForm.verify_mobile}
                                        onCheckedChange={(checked) =>
                                            setUserEditForm((current) => ({
                                                ...current,
                                                verify_mobile: checked === true,
                                            }))
                                        }
                                    />
                                    <div className="grid gap-1">
                                        <Label
                                            htmlFor="managed_user_verify_mobile"
                                            className="text-sm font-medium text-text"
                                        >
                                            تأیید شماره موبایل توسط ادمین
                                        </Label>
                                        <p className="text-xs text-muted">
                                            {confirmedUser.mobileVerified
                                                ? 'موبایل این کاربر تأیید شده است.'
                                                : 'با تغییر موبایل، به‌صورت پیش‌فرض تأیید می‌شود.'}
                                        </p>
                                    </div>
                                </div>

                                {userEditSuccess ? (
                                    <p className="text-xs font-medium text-green">
                                        {userEditSuccess}
                                    </p>
                                ) : null}

                                {userEditFieldError('general') ? (
                                    <p className="text-xs font-medium text-red">
                                        {userEditFieldError('general')}
                                    </p>
                                ) : null}

                                <AdminButton
                                    type="button"
                                    adminVariant="outline"
                                    disabled={userEditSaving}
                                    onClick={() => void saveUserInfo()}
                                >
                                    {userEditSaving
                                        ? 'در حال ذخیره...'
                                        : 'ذخیره اطلاعات کاربر'}
                                </AdminButton>
                            </div>
                        ) : null}

                        {flowMode === 'new_buyer' ? (
                            <div className="flex flex-col gap-4">
                                <span className="inline-flex w-fit rounded-pill bg-gold-soft px-3 py-1 text-xs font-bold text-gold">
                                    کاربر جدید
                                </span>
                                <div className="grid gap-2">
                                    <Label htmlFor="customer_name">نام</Label>
                                    <Input
                                        id="customer_name"
                                        value={data.customer_name}
                                        onChange={(event) =>
                                            setData(
                                                'customer_name',
                                                event.target.value,
                                            )
                                        }
                                        autoComplete="name"
                                    />
                                    <InputError
                                        message={errors.customer_name}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="customer_mobile">
                                        شماره موبایل
                                    </Label>
                                    <Input
                                        id="customer_mobile"
                                        value={data.customer_mobile}
                                        onChange={(event) =>
                                            setData(
                                                'customer_mobile',
                                                event.target.value,
                                            )
                                        }
                                        dir="ltr"
                                        inputMode="tel"
                                        autoComplete="tel"
                                        placeholder="09123456789"
                                    />
                                    <p className="text-xs text-muted">
                                        {data.user_lookup.trim() !== ''
                                            ? 'اگر موبایل در جستجو وارد شده باشد، می‌توانید همین مقدار را تأیید کنید.'
                                            : 'برای ساخت حساب جدید، شماره موبایل معتبر لازم است.'}
                                    </p>
                                    <InputError
                                        message={errors.customer_mobile}
                                    />
                                </div>
                            </div>
                        ) : null}

                        {flowMode === 'search_only' ? (
                            <div className="grid gap-4 border-t border-[#e8e0f0] pt-4">
                                <p className="text-xs font-medium text-muted">
                                    یا کاربر جدید (بدون جستجو)
                                </p>
                                <div className="grid gap-2">
                                    <Label htmlFor="new_customer_name">
                                        نام
                                    </Label>
                                    <Input
                                        id="new_customer_name"
                                        value={data.customer_name}
                                        onChange={(event) =>
                                            setData(
                                                'customer_name',
                                                event.target.value,
                                            )
                                        }
                                        autoComplete="name"
                                    />
                                    <InputError
                                        message={errors.customer_name}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="new_customer_mobile">
                                        شماره موبایل
                                    </Label>
                                    <Input
                                        id="new_customer_mobile"
                                        value={data.customer_mobile}
                                        onChange={(event) =>
                                            setData(
                                                'customer_mobile',
                                                event.target.value,
                                            )
                                        }
                                        dir="ltr"
                                        inputMode="tel"
                                        autoComplete="tel"
                                        placeholder="09123456789"
                                    />
                                    <InputError
                                        message={errors.customer_mobile}
                                    />
                                </div>
                            </div>
                        ) : null}
                    </section>

                    <section
                        className={cn(
                            surfaceCardClassName,
                            'flex flex-col gap-4 p-4 sm:p-5',
                        )}
                    >
                        <AdminSectionTitle>
                            ۳. ثبت دسترسی دوره
                        </AdminSectionTitle>

                        <p className="text-xs leading-relaxed text-muted">
                            سفارش و پرداخت خارج از سایت ثبت می‌شود. با کلید
                            لایسنس یا فعال‌سازی API، دسترسی{' '}
                            <span className="font-bold text-green">فعال</span>{' '}
                            می‌شود؛ در غیر این صورت لایسنس در حالت{' '}
                            <span className="font-bold text-gold">
                                انتظار
                            </span>{' '}
                            می‌ماند.
                        </p>

                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="course_package_id">
                                    بسته دوره
                                </Label>
                                <Select
                                    value={data.course_package_id}
                                    onValueChange={(value) =>
                                        setData('course_package_id', value)
                                    }
                                >
                                    <SelectTrigger id="course_package_id">
                                        <SelectValue placeholder="انتخاب بسته" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {packages.map((pkg) => (
                                            <SelectItem
                                                key={pkg.id}
                                                value={String(pkg.id)}
                                            >
                                                {pkg.title} —{' '}
                                                {pkg.priceFormatted}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.course_package_id}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="source">منبع خرید</Label>
                                <Select
                                    value={data.source}
                                    onValueChange={(value) =>
                                        setData('source', value)
                                    }
                                >
                                    <SelectTrigger id="source">
                                        <SelectValue placeholder="انتخاب منبع" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {sourceOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.source} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="admin_note">
                                    یادداشت ادمین (اختیاری)
                                </Label>
                                <textarea
                                    id="admin_note"
                                    className={textareaClassName}
                                    value={data.admin_note}
                                    onChange={(event) =>
                                        setData(
                                            'admin_note',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.admin_note} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="license_key">
                                    کلید لایسنس SpotPlayer (اختیاری)
                                </Label>
                                <Input
                                    id="license_key"
                                    value={data.license_key}
                                    onChange={(event) =>
                                        setData(
                                            'license_key',
                                            event.target.value,
                                        )
                                    }
                                    dir="ltr"
                                    placeholder="در صورت صدور قبلی کلید را وارد کنید"
                                />
                                <InputError message={errors.license_key} />
                            </div>
                        </div>

                        {submitHint ? (
                            <p className="text-xs font-medium text-red">
                                {submitHint}
                            </p>
                        ) : null}

                        <AdminButton
                            type="submit"
                            disabled={
                                processing || !submitReadiness.ready
                            }
                        >
                            ثبت دسترسی
                        </AdminButton>
                    </section>
                </form>

                <section
                    className={cn(
                        surfaceCardClassName,
                        'flex flex-col gap-4 p-4 sm:p-5',
                    )}
                >
                    <AdminSectionTitle>
                        ثبت‌های اخیر (خارج از سایت)
                    </AdminSectionTitle>

                    {recentGrants.length === 0 ? (
                        <AdminEmptyState message="هنوز ثبت دستی انجام نشده است." />
                    ) : (
                        <div className="flex flex-col gap-3">
                            {recentGrants.map((grant) => (
                                <div
                                    key={grant.id}
                                    className="rounded-2xl border border-[#e8e0f0] bg-bg p-4"
                                >
                                    <p className="text-sm font-bold text-text">
                                        {grant.orderNumber}
                                    </p>
                                    <p className="mt-1 text-xs text-muted">
                                        {grant.packageTitle}
                                    </p>
                                    <AdminInfoGrid className="mt-3">
                                        <AdminDetailRow
                                            label="نام"
                                            value={grant.customerName}
                                            truncateValue
                                        />
                                        <AdminDetailRow
                                            label="موبایل"
                                            value={grant.customerMobile}
                                        />
                                        <AdminDetailRow
                                            label="منبع"
                                            value={grant.sourceLabel}
                                        />
                                        <AdminDetailRow
                                            label="لایسنس"
                                            value={grant.licenseStatus}
                                        />
                                        <AdminDetailRow
                                            label="تاریخ"
                                            value={formatAdminDate(
                                                grant.createdAt,
                                            )}
                                        />
                                    </AdminInfoGrid>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
