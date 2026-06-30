import { Head } from '@inertiajs/react';
import { CourseHomeExercisesPreview } from '@/components/course/course-home-exercises-preview';
import { CourseHomeHeader } from '@/components/course/course-home-header';
import { CourseHomeOnboardingSection } from '@/components/course/course-home-onboarding-section';
import { CourseHomeProfileFootnote } from '@/components/course/course-home-profile-footnote';
import { CourseHomeResourcesPreview } from '@/components/course/course-home-resources-preview';
import { CourseHomeUpdatesPreview } from '@/components/course/course-home-updates-preview';
import { PageContainer } from '@/components/page-container';
import type { CourseHomePageProps } from '@/lib/course-home-data';

export default function CourseHome({
    progress,
    onboarding,
    showGettingStartedSection,
    notifications,
    preview,
}: CourseHomePageProps) {
    return (
        <>
            <Head title="پنل هنرجو" />
            <PageContainer>
                <div className="flex flex-col gap-5">
                    <CourseHomeHeader
                        progress={progress}
                        medals={preview.medals}
                        notifications={notifications}
                    />
                    {showGettingStartedSection && (
                        <CourseHomeOnboardingSection onboarding={onboarding} />
                    )}
                    <CourseHomeExercisesPreview
                        exercisesSummary={preview.exercisesSummary}
                        visual={preview.sectionVisuals.exercises}
                    />
                    <CourseHomeResourcesPreview
                        resources={preview.resources}
                        resourcesIndexUrl={preview.resourcesIndexUrl}
                        visual={preview.sectionVisuals.resources}
                    />
                    <CourseHomeUpdatesPreview
                        updates={preview.updates}
                        visual={preview.sectionVisuals.updates}
                    />
                    <CourseHomeProfileFootnote />
                </div>
            </PageContainer>
        </>
    );
}
