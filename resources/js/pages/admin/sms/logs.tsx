import { Head, Link } from '@inertiajs/react';
import { SmsLogsList } from '@/components/admin/sms-logs-list';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSearchBar } from '@/components/admin/admin-search-bar';
import type { AdminPaginated, AdminSmsLogItem } from '@/types/admin';

type PageProps = {
    logs: AdminPaginated<AdminSmsLogItem>;
    filters: { q: string | null };
};

export default function AdminSmsLogsIndex({ logs, filters }: PageProps) {
    return (
        <>
            <Head title="گزارش پیامک‌ها" />
            <AdminPageHeader
                title="گزارش پیامک‌ها"
                description="لیست ارسال و وضعیت پیامک‌های ثبت‌شده"
                actions={
                    <AdminButton asChild size="sm" adminVariant="outline">
                        <Link href="/admin/sms">بازگشت به تنظیمات</Link>
                    </AdminButton>
                }
            />

            <AdminSearchBar
                basePath="/admin/sms/logs"
                placeholder="جستجو بر اساس موبایل، متن پیام، نوع..."
                value={filters.q}
            />

            <div className="flex flex-col gap-3">
                <SmsLogsList logs={logs} isSearchActive={Boolean(filters.q)} />
            </div>
        </>
    );
}
