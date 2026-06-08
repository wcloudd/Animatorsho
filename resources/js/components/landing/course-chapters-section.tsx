import { useState } from 'react';
import { LandingMediaVideo } from '@/components/landing/landing-media-video';
import {
    LANDING_COURSE_CHAPTERS,
    type CourseChapterMediaSlot,
} from '@/lib/landing-media';
import { cn } from '@/lib/utils';

const LESSONS_INITIAL_COUNT = 7;

type CourseChapter = CourseChapterMediaSlot;

const COURSE_CHAPTERS = LANDING_COURSE_CHAPTERS;

function ChapterTab({
    chapter,
    isActive,
    onSelect,
}: {
    chapter: CourseChapter;
    isActive: boolean;
    onSelect: () => void;
}) {
    return (
        <button
            type="button"
            role="tab"
            aria-selected={isActive}
            onClick={onSelect}
            className={cn(
                'shrink-0 rounded-pill px-4 py-2 text-sm font-medium transition-colors',
                isActive
                    ? 'bg-text text-white'
                    : 'bg-surface text-muted shadow-none ring-0',
            )}
        >
            {chapter.shortTab}
        </button>
    );
}

function ChapterContentCard({
    chapter,
    expanded,
    onExpand,
}: {
    chapter: CourseChapter;
    expanded: boolean;
    onExpand: () => void;
}) {
    const visibleLessons = expanded
        ? chapter.lessons
        : chapter.lessons.slice(0, LESSONS_INITIAL_COUNT);
    const hasMoreLessons = chapter.lessons.length > LESSONS_INITIAL_COUNT;

    return (
        <div
            role="tabpanel"
            className="mx-auto flex w-[341px] flex-col justify-center gap-5 rounded-[28px] bg-[#e9e7e5] p-4"
        >
            <LandingMediaVideo
                key={chapter.id}
                videoSrc={chapter.videoSrc}
                posterSrc={chapter.posterSrc}
                ariaLabel={`ویدیو ${chapter.title}`}
                aspectClassName="h-[240px]"
                className="rounded-2xl"
                enabled
            />

            <div className="flex flex-col items-start gap-3 text-right">
                <h3 className="text-base font-bold text-text">{chapter.title}</h3>

                <div className="flex flex-wrap items-center gap-2">
                    <span className="rounded-pill bg-purple-soft px-2.5 py-1 text-xs font-medium text-purple">
                        {chapter.durationLabel}
                    </span>
                    {chapter.updateLabel ? (
                        <span className="rounded-pill bg-green-soft px-2.5 py-1 text-xs font-medium text-green">
                            {chapter.updateLabel}
                        </span>
                    ) : null}
                </div>

                <ul className="flex w-full flex-col gap-2.5">
                    {visibleLessons.map((lesson) => (
                        <li
                            key={lesson}
                            className="text-sm leading-relaxed text-text"
                        >
                            {lesson}
                        </li>
                    ))}
                </ul>

                {hasMoreLessons && !expanded ? (
                    <button
                        type="button"
                        onClick={onExpand}
                        className="inline-flex items-baseline gap-1 text-sm text-muted transition-colors hover:text-text"
                    >
                        <span className="border-b border-dotted border-current pb-0.5">
                            مشاهده بیشتر
                        </span>
                        <span aria-hidden="true">...</span>
                    </button>
                ) : null}
            </div>
        </div>
    );
}

export function CourseChaptersSection() {
    const [activeChapterId, setActiveChapterId] = useState(
        COURSE_CHAPTERS[0].id,
    );
    const [expanded, setExpanded] = useState(false);

    const activeChapter =
        COURSE_CHAPTERS.find((chapter) => chapter.id === activeChapterId) ??
        COURSE_CHAPTERS[0];

    function selectChapter(chapterId: string) {
        setActiveChapterId(chapterId);
        setExpanded(false);
    }

    return (
        <section
            id="course-chapters"
            className="flex w-full scroll-mt-24 flex-col items-center gap-6 px-4 py-[62px] text-center"
            aria-labelledby="course-chapters-heading"
        >
            <h2
                id="course-chapters-heading"
                className="font-display pb-[15px] text-[1.75rem] leading-tight font-bold text-text"
            >
                <span className="block">با انیماتورشو</span>
                <span className="mt-1 block">چی یاد می‌گیری؟</span>
            </h2>

            <div className="w-full min-w-0">
                <div
                    role="tablist"
                    aria-label="سرفصل‌های دوره"
                    className="-mx-4 flex min-w-0 flex-nowrap gap-2 overflow-x-auto overscroll-x-contain px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    {COURSE_CHAPTERS.map((chapter) => (
                        <ChapterTab
                            key={chapter.id}
                            chapter={chapter}
                            isActive={chapter.id === activeChapterId}
                            onSelect={() => selectChapter(chapter.id)}
                        />
                    ))}
                </div>
            </div>

            <ChapterContentCard
                chapter={activeChapter}
                expanded={expanded}
                onExpand={() => setExpanded(true)}
            />
        </section>
    );
}
