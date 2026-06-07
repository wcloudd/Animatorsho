import { Head, Link } from '@inertiajs/react';
import { SmsLogsList } from '@/components/admin/sms-logs-list';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import type { AdminPaginated, AdminSmsLogItem } from '@/types/admin';

type PageProps = {
    logs: AdminPaginated<AdminSmsLogItem>;
};

export default function AdminSmsLogsIndex({ logs }: PageProps) {
    return (
        <>
            <Head title="گزارش پیامک‌ها" />
            <AdminPageHeader
                title="گزارش پیامک‌ها"
                description="لیست ارسال و وضعیت پیامک‌های ثبت‌شده"
                actions={
                    <Link
                        href="/admin/sms"
                        className="text-sm font-medium text-purple underline-offset-2 hover:underline"
                    >
                        بازگشت به تنظیمات
                    </Link>
                }
            />

            <div className="flex flex-col gap-3">
                <SmsLogsList logs={logs} />
            </div>
        </>
    );
}
