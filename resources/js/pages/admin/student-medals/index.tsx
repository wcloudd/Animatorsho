import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import InputError from '@/components/input-error';
import { surfaceCardClassName } from '@/components/page-container';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { userSuggestions as userSuggestionsRoute } from '@/routes/admin/manual-enrollments';

type MedalOption = {
    key: string;
    title: string;
};

type RecentAward = {
    id: number;
    studentName: string;
    studentMobile: string | null;
    medalKey: string;
    medalTitle: string;
    awardedAtLabel: string;
    awardedByName: string;
    note: string | null;
};

type UserSuggestion = {
    id: number;
    name: string;
    mobile: string | null;
    label: string;
};

type PageProps = {
    medals: MedalOption[];
    recentAwards: RecentAward[];
};

const SUGGESTIONS_DEBOUNCE_MS = 300;

function readXsrfToken(): string {
    const token = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return token ? decodeURIComponent(token) : '';
}

export default function AdminStudentMedals({
    medals,
    recentAwards,
}: PageProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        user_id: '' as string | number,
        medal_key: '',
        note: '',
    });

    const [userSearch, setUserSearch] = useState('');
    const [selectedUser, setSelectedUser] = useState<UserSuggestion | null>(
        null,
    );
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
        post('/admin/student-medals', {
            onSuccess: () => {
                reset();
                clearUser();
            },
        });
    }

    function handleRevoke(id: number): void {
        if (!confirm('آیا مطمئن هستید؟ این مدال از هنرجو حذف می‌شود.')) {
            return;
        }

        router.delete(`/admin/student-medals/${id}`);
    }

    return (
        <>
            <Head title="مدال‌های هنرجوها" />
            <AdminPageHeader
                title="مدال‌های هنرجوها"
                description="اعطای دستی مدال به هنرجویان"
            />

            <div className="grid gap-6 lg:grid-cols-2">
                <section className={cn(surfaceCardClassName, 'flex flex-col gap-5')}>
                    <AdminSectionTitle>اعطای مدال</AdminSectionTitle>

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
                            <Label htmlFor="medal-select">مدال</Label>
                            <Select
                                value={data.medal_key}
                                onValueChange={(v) => setData('medal_key', v)}
                            >
                                <SelectTrigger id="medal-select">
                                    <SelectValue placeholder="انتخاب مدال..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {medals.map((medal) => (
                                        <SelectItem
                                            key={medal.key}
                                            value={medal.key}
                                        >
                                            {medal.title}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.medal_key} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="note-input">
                                یادداشت{' '}
                                <span className="font-normal text-muted">
                                    (اختیاری)
                                </span>
                            </Label>
                            <Input
                                id="note-input"
                                type="text"
                                placeholder="یادداشت برای این مدال..."
                                value={data.note}
                                onChange={(e) =>
                                    setData('note', e.target.value)
                                }
                                maxLength={500}
                            />
                            <InputError message={errors.note} />
                        </div>

                        <AdminButton
                            type="submit"
                            adminVariant="primary"
                            disabled={
                                processing ||
                                selectedUser === null ||
                                data.medal_key === ''
                            }
                        >
                            {processing ? 'در حال ثبت...' : 'اعطای مدال'}
                        </AdminButton>
                    </form>
                </section>

                <section className="flex flex-col gap-4">
                    <AdminSectionTitle>مدال‌های اخیر</AdminSectionTitle>

                    {recentAwards.length === 0 ? (
                        <AdminEmptyState message="هنوز مدالی ثبت نشده است." />
                    ) : (
                        <ul className="flex flex-col gap-2">
                            {recentAwards.map((award) => (
                                <li
                                    key={award.id}
                                    className={cn(
                                        surfaceCardClassName,
                                        'flex items-start justify-between gap-3',
                                    )}
                                >
                                    <div className="flex min-w-0 flex-col gap-0.5">
                                        <span className="text-sm font-bold text-text">
                                            {award.medalTitle}
                                        </span>
                                        <span className="text-xs font-medium text-muted">
                                            {award.studentName}
                                            {award.studentMobile
                                                ? ` · ${award.studentMobile}`
                                                : ''}
                                        </span>
                                        <span className="text-xs font-medium text-muted">
                                            {award.awardedAtLabel} · توسط{' '}
                                            {award.awardedByName}
                                        </span>
                                        {award.note ? (
                                            <span className="text-xs font-medium text-muted/80">
                                                {award.note}
                                            </span>
                                        ) : null}
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => handleRevoke(award.id)}
                                        className="shrink-0 text-muted hover:text-red"
                                        title="لغو مدال"
                                    >
                                        <Trash2 className="size-4" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}
