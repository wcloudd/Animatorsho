import { Head, Link } from '@inertiajs/react';
import { BookOpen, ChevronLeft } from 'lucide-react';
import { CourseResourceRow } from '@/components/course/course-resource-row';
import { PageContainer } from '@/components/page-container';
import type { CourseResourcesIndexPageProps } from '@/lib/course-resources-data';

export default function CourseResources({
    groups,
    totalCount,
}: CourseResourcesIndexPageProps) {
    const hasResources = totalCount > 0;

    return (
        <>
            <Head title="کتابخانه تمرین" />
            <PageContainer>
                <div className="flex flex-col gap-5">
                    <Link
                        href="/course"
                        className="inline-flex items-center gap-1 self-start text-xs font-bold text-purple"
                    >
                        <ChevronLeft className="size-4" />
                        بازگشت به پنل هنرجو
                    </Link>

                    <header className="flex flex-col items-center gap-3 text-center">
                        <span className="inline-flex items-center gap-1.5 rounded-pill bg-purple-soft px-3 py-1 text-[11px] font-bold text-purple ring-1 ring-purple/15">
                            <BookOpen className="size-3.5" />
                            منابع دوره
                        </span>
                        <h1 className="font-display text-[1.625rem] leading-tight font-bold text-text">
                            کتابخانه تمرین
                        </h1>
                        <p className="max-w-[320px] text-sm font-medium leading-relaxed text-muted">
                            فایل‌های تمرین، رفرنس‌ها، PDFها و لینک‌های کمکی
                            دوره
                        </p>
                    </header>

                    {!hasResources ? (
                        <p className="rounded-2xl bg-surface px-4 py-4 text-sm font-medium leading-relaxed text-muted shadow-soft ring-1 ring-border">
                            هنوز منبعی برای این بخش منتشر نشده است.
                        </p>
                    ) : (
                        <div className="flex flex-col gap-5">
                            {groups.map((group) => (
                                <section
                                    key={group.id}
                                    className="flex flex-col gap-3 rounded-[28px] bg-surface px-4 py-4 shadow-soft ring-1 ring-border"
                                >
                                    <h2 className="text-sm font-bold text-text">
                                        {group.title}
                                    </h2>
                                    <ul className="flex flex-col gap-2.5">
                                        {group.resources.map((resource) => (
                                            <li key={resource.id}>
                                                <CourseResourceRow
                                                    resource={resource}
                                                    showCategory={false}
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ))}
                        </div>
                    )}
                </div>
            </PageContainer>
        </>
    );
}
