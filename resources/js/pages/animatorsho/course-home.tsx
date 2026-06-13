import { Head } from '@inertiajs/react';
import { CourseHomeExercisesPreview } from '@/components/course/course-home-exercises-preview';
import { CourseHomeHeader } from '@/components/course/course-home-header';
import { CourseHomeMedalsShowcase } from '@/components/course/course-home-medals-showcase';
import { CourseHomeMentorPreview } from '@/components/course/course-home-mentor-preview';
import { CourseHomeOnboardingSection } from '@/components/course/course-home-onboarding-section';
import { CourseHomeProfileFootnote } from '@/components/course/course-home-profile-footnote';
import { CourseHomeResourcesPreview } from '@/components/course/course-home-resources-preview';
import { CourseHomeUpdatesPreview } from '@/components/course/course-home-updates-preview';
import { PageContainer } from '@/components/page-container';
import type { CourseHomePageProps } from '@/lib/course-home-data';

export default function CourseHome({
    welcome,
    progress,
    onboarding,
    preview,
}: CourseHomePageProps) {
    return (
        <>
            <Head title="پنل هنرجو" />
            <PageContainer>
                <div className="flex flex-col gap-5">
                    <CourseHomeHeader
                        welcome={welcome}
                        progress={progress}
                        notificationsUnread={preview.notificationsUnread}
                    />
                    <CourseHomeOnboardingSection onboarding={onboarding} />
                    <CourseHomeExercisesPreview
                        exercisesSummary={preview.exercisesSummary}
                        visual={preview.sectionVisuals.exercises}
                    />
                    <CourseHomeMentorPreview
                        mentorSummary={preview.mentorSummary}
                        visual={preview.sectionVisuals.mentor}
                    />
                    <CourseHomeResourcesPreview
                        resources={preview.resources}
                        visual={preview.sectionVisuals.resources}
                    />
                    <CourseHomeMedalsShowcase
                        medals={preview.medals}
                        visual={preview.sectionVisuals.medals}
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
