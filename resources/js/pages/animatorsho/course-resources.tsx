import { Head, Link } from '@inertiajs/react';
import { BookOpen, ChevronLeft } from 'lucide-react';
import { useMemo, useState } from 'react';
import { CourseResourceMasonryGrid } from '@/components/course/course-resource-masonry-grid';
import { CourseResourceRow } from '@/components/course/course-resource-row';
import { PageContainer } from '@/components/page-container';
import type { CourseResourcesIndexPageProps } from '@/lib/course-resources-data';
import { cn } from '@/lib/utils';

export default function CourseResources({
    categories,
    sections,
    totalCount,
}: CourseResourcesIndexPageProps) {
    const [activeFilter, setActiveFilter] = useState('all');
    const hasResources = totalCount > 0;

    const filteredSections = useMemo(() => {
        if (activeFilter === 'all') {
            return sections;
        }

        return sections.filter((section) => section.id === activeFilter);
    }, [activeFilter, sections]);

    const filteredCount = useMemo(
        () =>
            filteredSections.reduce(
                (count, section) => count + section.resources.length,
                0,
            ),
        [filteredSections],
    );

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

                    {hasResources ? (
                        <>
                            <div className="flex flex-wrap gap-2">
                                {categories.map((category) => (
                                    <button
                                        key={category.id}
                                        type="button"
                                        onClick={() =>
                                            setActiveFilter(category.id)
                                        }
                                        className={cn(
                                            'rounded-pill px-3 py-1.5 text-xs font-bold transition-colors',
                                            activeFilter === category.id
                                                ? 'bg-purple text-white'
                                                : 'bg-surface text-muted ring-1 ring-border/70 hover:bg-purple-soft/30',
                                        )}
                                    >
                                        {category.label}
                                    </button>
                                ))}
                            </div>

                            {filteredCount === 0 ? (
                                <p className="rounded-2xl bg-surface px-4 py-4 text-sm font-medium leading-relaxed text-muted shadow-soft ring-1 ring-border">
                                    در این دسته هنوز منبعی منتشر نشده است.
                                </p>
                            ) : (
                                <div className="flex flex-col gap-5">
                                    {filteredSections.map((section) => (
                                        <section
                                            key={section.id}
                                            className="flex flex-col gap-3 rounded-[28px] bg-surface px-4 py-4 shadow-soft ring-1 ring-border"
                                        >
                                            <h2 className="text-sm font-bold text-text">
                                                {section.title}
                                            </h2>
                                            {section.layout === 'masonry' ? (
                                                <CourseResourceMasonryGrid
                                                    resources={
                                                        section.resources
                                                    }
                                                />
                                            ) : (
                                                <ul className="flex flex-col gap-2.5">
                                                    {section.resources.map(
                                                        (resource) => (
                                                            <li
                                                                key={resource.id}
                                                            >
                                                                <CourseResourceRow
                                                                    resource={
                                                                        resource
                                                                    }
                                                                    showCategory={
                                                                        false
                                                                    }
                                                                />
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            )}
                                        </section>
                                    ))}
                                </div>
                            )}
                        </>
                    ) : (
                        <p className="rounded-2xl bg-surface px-4 py-4 text-sm font-medium leading-relaxed text-muted shadow-soft ring-1 ring-border">
                            هنوز منبعی برای این بخش منتشر نشده است.
                        </p>
                    )}
                </div>
            </PageContainer>
        </>
    );
}
