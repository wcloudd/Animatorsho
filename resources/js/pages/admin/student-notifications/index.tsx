import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import InputError from '@/components/input-error';
import { surfaceCardClassName } from '@/components/page-container';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { userSuggestions as userSuggestionsRoute } from '@/routes/admin/manual-enrollments';

type UserSuggestion = {
    id: number;
    name: string;
    mobile: string | null;
    label: string;
};

const SUGGESTIONS_DEBOUNCE_MS = 300;

function readXsrfToken(): string {
    const token = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return token ? decodeURIComponent(token) : '';
}

export default function AdminStudentNotifications() {
    const { data, setData, post, processing, errors, reset } = useForm({
        user_id: '' as string | number,
        title: '',
        body: '',
        action_url: '',
    });

    const [userSearch, setUserSearch] = useState('');
    const [selectedUser, setSelectedUser] = useState<UserSuggestion | null>(null);
    const [suggestions, setSuggestions] = useState<UserSuggestion[]>([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const q = userSearch.trim();

        if (q.length < 2 || selectedUser !== null) {
            setSuggestions([]);

            return;
        }

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(async () => {
            try {
                const url = userSuggestionsRoute.url({ query: { q } });
                const res = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': readXsrfToken(),
                    },
                    credentials: 'same-origin',
                });

                if (res.ok) {
                    const json = (await res.json()) as {
                        suggestions: UserSuggestion[];
                    };
                    setSuggestions(json.suggestions ?? []);
                    setShowSuggestions(true);
                }
            } catch {
                // silently ignore
            }
        }, SUGGESTIONS_DEBOUNCE_MS);
    }, [userSearch, selectedUser]);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent): void {
            if (
                containerRef.current &&
                !containerRef.current.contains(e.target as Node)
            ) {
                setShowSuggestions(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    function selectUser(user: UserSuggestion): void {
        setSelectedUser(user);
        setUserSearch(user.label);
        setData('user_id', user.id);
        setShowSuggestions(false);
        setSuggestions([]);
    }

    function clearUser(): void {
        setSelectedUser(null);
        setUserSearch('');
        setData('user_id', '');
    }

    function handleSubmit(e: FormEvent): void {
        e.preventDefault();
        post('/admin/student-notifications', {
            onSuccess: () => {
                reset();
                clearUser();
            },
        });
    }

    const canSubmit =
        !processing &&
        selectedUser !== null &&
        data.title.trim() !== '' &&
        data.body.trim() !== '';

    return (
        <>
            <Head title="پیام دستی به هنرجو" />
            <AdminPageHeader
                title="پیام دستی به هنرجو"
                description="ارسال اعلان مستقیم به پنل هنرجو"
            />

            <div className="max-w-xl">
                <section className={cn(surfaceCardClassName, 'flex flex-col gap-5')}>
                    <AdminSectionTitle>ارسال پیام</AdminSectionTitle>

                    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="user-search">هنرجو</Label>
                            <div ref={containerRef} className="relative">
                                <Input
                                    id="user-search"
                                    type="text"
                                    placeholder="جستجو با نام، موبایل، یا نام کاربری..."
                                    value={userSearch}
                                    onChange={(e) => {
                                        setUserSearch(e.target.value);
                                        if (selectedUser !== null) {
                                            clearUser();
                                        }
                                    }}
                                    onFocus={() => {
                                        if (suggestions.length > 0) {
                                            setShowSuggestions(true);
                                        }
                                    }}
                                    autoComplete="off"
                                    className={cn(
                                        selectedUser !== null &&
                                            'border-green/60 bg-green-soft/30',
                                    )}
                                />
                                {showSuggestions && suggestions.length > 0 ? (
                                    <ul className="absolute z-10 mt-1 w-full rounded-xl border border-border bg-surface shadow-soft">
                                        {suggestions.map((s) => (
                                            <li key={s.id}>
                                                <button
                                                    type="button"
                                                    className="w-full px-3 py-2 text-start text-sm font-medium text-text hover:bg-purple-soft/50"
                                                    onMouseDown={() =>
                                                        selectUser(s)
                                                    }
                                                >
                                                    {s.label}
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                ) : null}
                            </div>
                            {selectedUser !== null ? (
                                <p className="flex items-center gap-2 text-xs font-medium text-green">
                                    کاربر انتخاب شد: {selectedUser.name}
                                    <button
                                        type="button"
                                        onClick={clearUser}
                                        className="text-muted underline hover:text-red"
                                    >
                                        تغییر
                                    </button>
                                </p>
                            ) : null}
                            <InputError message={errors.user_id} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="title-input">عنوان پیام</Label>
                            <Input
                                id="title-input"
                                type="text"
                                placeholder="مثال: پیام مهم از ادمین"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                maxLength={255}
                            />
                            <InputError message={errors.title} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="body-input">متن پیام</Label>
                            <textarea
                                id="body-input"
                                placeholder="متن پیام برای هنرجو..."
                                value={data.body}
                                onChange={(e) => setData('body', e.target.value)}
                                maxLength={1000}
                                rows={4}
                                className="flex min-h-[80px] w-full rounded-xl border border-input bg-bg px-3 py-2 text-sm font-medium text-text placeholder:text-muted focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-purple disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError message={errors.body} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="action-url-input">
                                لینک اقدام{' '}
                                <span className="font-normal text-muted">
                                    (اختیاری)
                                </span>
                            </Label>
                            <Input
                                id="action-url-input"
                                type="text"
                                placeholder="مثال: /course/exercises"
                                value={data.action_url}
                                onChange={(e) =>
                                    setData('action_url', e.target.value)
                                }
                                maxLength={500}
                                dir="ltr"
                            />
                            <InputError message={errors.action_url} />
                        </div>

                        <AdminButton
                            type="submit"
                            adminVariant="primary"
                            disabled={!canSubmit}
                        >
                            {processing ? 'در حال ارسال...' : 'ارسال پیام'}
                        </AdminButton>
                    </form>
                </section>
            </div>
        </>
    );
}
