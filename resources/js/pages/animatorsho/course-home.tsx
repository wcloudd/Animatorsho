import { Head } from '@inertiajs/react';
import { CourseHomeChaptersSection } from '@/components/course/course-home-chapters-section';
import { CourseHomePlaceholderSection } from '@/components/course/course-home-placeholder-section';
import { CourseHomeSpotPlayerSection } from '@/components/course/course-home-spotplayer-section';
import { CourseHomeSupportSection } from '@/components/course/course-home-support-section';
import { CourseHomeWelcomeCard } from '@/components/course/course-home-welcome-card';
import { PageContainer } from '@/components/page-container';
import type { CourseHomePageProps } from '@/lib/course-home-data';

export default function CourseHome({
    welcome,
    chapters,
    spotPlayerLicenses,
}: CourseHomePageProps) {
    return (
        <>
            <Head title="دوره انیماتورشو" />
            <PageContainer>
                <div className="flex flex-col gap-6">
                    <CourseHomeWelcomeCard welcome={welcome} />
                    <CourseHomeChaptersSection
                        chapters={chapters}
                        hasFullAccess={welcome.hasFullAccess}
                    />
                    <CourseHomePlaceholderSection
                        title="تمرین‌ها"
                        description="تمرین‌های هر فصل به‌زودی از این بخش در دسترس قرار می‌گیرند."
                    />
                    <CourseHomePlaceholderSection
                        title="فایل‌های تمرین"
                        description="فایل‌های قابل دانلود تمرین‌ها به‌زودی اینجا قرار می‌گیرند."
                    />
                    <CourseHomePlaceholderSection
                        title="ورکشاپ‌ها"
                        description="ورکشاپ‌های تکمیلی دوره به‌زودی از این بخش معرفی می‌شوند."
                    />
                    <CourseHomePlaceholderSection
                        title="ارتباط با استاد"
                        description="امکان ارتباط مستقیم با استاد به‌زودی از این بخش فعال می‌شود."
                    />
                    {spotPlayerLicenses.length > 0 ? (
                        <CourseHomeSpotPlayerSection
                            licenses={spotPlayerLicenses}
                        />
                    ) : null}
                    <CourseHomeSupportSection />
                </div>
            </PageContainer>
        </>
    );
}
