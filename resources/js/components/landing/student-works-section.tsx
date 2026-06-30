import { Play } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { LandingVideoModal } from '@/components/landing/landing-video-modal';
import {
    LANDING_STUDENT_WORKS,
    type StudentWorkMediaSlot,
} from '@/lib/landing-media';
import { useHorizontalScrollInteractions } from '@/hooks/use-horizontal-scroll-interactions';
import { cn } from '@/lib/utils';

type StudentWork = StudentWorkMediaSlot;

const STUDENT_WORKS = LANDING_STUDENT_WORKS;

const CARD_WIDTH_PX = 280;

function StudentWorkCard({
    work,
    isActive,
    cardRef,
    onOpenVideo,
}: {
    work: StudentWork;
    isActive: boolean;
    cardRef: (node: HTMLDivElement | null) => void;
    onOpenVideo: (work: StudentWork) => void;
}) {
    const [posterFailed, setPosterFailed] = useState(false);

    return (
        <div
            ref={cardRef}
            data-student-work-id={work.id}
            className={cn(
                'w-[280px] shrink-0 snap-center scroll-ml-[calc((100%-280px)/2)] transition-[transform,opacity] duration-300',
                isActive ? 'scale-100 opacity-100' : 'scale-[0.94] opacity-70',
            )}
        >
            <div className="flex flex-col gap-4 rounded-[28px] bg-[#e9e7e5] p-4">
                <button
                    type="button"
                    onClick={() => onOpenVideo(work)}
                    className="relative aspect-square w-full overflow-hidden rounded-2xl bg-[#f0f7f9]"
                    aria-label={`پخش ویدیو ${work.projectTitle} — ${work.studentName}`}
                >
                    {!posterFailed ? (
                        <img
                            src={work.posterSrc}
                            alt=""
                            className="block h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                            onError={() => setPosterFailed(true)}
                        />
                    ) : null}
                    <span className="absolute inset-0 flex items-center justify-center">
                        <span className="flex size-14 items-center justify-center rounded-full bg-white/90 shadow-soft">
                            <Play
                                className="ms-0.5 size-6 fill-muted text-muted"
                                aria-hidden
                            />
                        </span>
                    </span>
                </button>

                <div className="flex items-center gap-3 text-right">
                    <Avatar className="size-11 shrink-0 ring-2 ring-surface">
                        <AvatarImage
                            src={work.avatarSrc}
                            alt={work.studentName}
                            loading="lazy"
                            decoding="async"
                        />
                        <AvatarFallback className="bg-purple-soft text-sm font-medium text-purple">
                            {work.studentName.charAt(0)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="flex min-w-0 flex-1 flex-col items-start gap-1">
                        <p className="w-full truncate text-base font-bold text-text">
                            {work.studentName}
                        </p>
                        {work.projectTitle ? (
                            <p className="w-full truncate text-xs font-medium text-muted">
                                {work.projectTitle}
                            </p>
                        ) : null}
                        <Badge
                            variant="outline"
                            className="rounded-pill border-transparent bg-purple-soft px-2.5 py-0.5 text-xs font-medium text-purple"
                        >
                            {work.badgeLabel}
                        </Badge>
                    </div>
                </div>
            </div>
        </div>
    );
}


export function StudentWorksSection() {
    const [activeIndex, setActiveIndex] = useState(0);
    const [selectedWork, setSelectedWork] = useState<StudentWork | null>(null);
    const scrollRef = useHorizontalScrollInteractions();
    const cardRefs = useRef<(HTMLDivElement | null)[]>([]);

    const setCardRef = useCallback(
        (index: number) => (node: HTMLDivElement | null) => {
            cardRefs.current[index] = node;
        },
        [],
    );

    useEffect(() => {
        const scrollContainer = scrollRef.current;
        if (!scrollContainer) {
            return;
        }

        const cards = cardRefs.current.filter(Boolean) as HTMLDivElement[];
        if (cards.length === 0) {
            return;
        }

        const visibleRatios = new Array<number>(cards.length).fill(0);

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    const index = cards.findIndex((card) => card === entry.target);
                    if (index >= 0) {
                        visibleRatios[index] = entry.intersectionRatio;
                    }
                });

                let bestIndex = 0;
                let bestRatio = visibleRatios[0] ?? 0;

                visibleRatios.forEach((ratio, index) => {
                    if (ratio > bestRatio) {
                        bestRatio = ratio;
                        bestIndex = index;
                    }
                });

                setActiveIndex(bestIndex);
            },
            {
                root: scrollContainer,
                threshold: [0, 0.25, 0.5, 0.75, 1],
            },
        );

        cards.forEach((card) => observer.observe(card));

        return () => observer.disconnect();
    }, []);

    function scrollToIndex(index: number) {
        cardRefs.current[index]?.scrollIntoView({
            behavior: 'smooth',
            inline: 'center',
            block: 'nearest',
        });
        setActiveIndex(index);
    }

    return (
        <section
            id="student-works"
            className="flex w-full scroll-mt-24 flex-col items-center gap-8 px-4 py-12 text-center"
            aria-labelledby="student-works-heading"
        >
            <div className="flex w-full flex-col items-center gap-3">
                <h2
                    id="student-works-heading"
                    className="font-display max-w-[354px] text-[1.75rem] leading-tight font-bold"
                >
                    <span className="block text-gradient-animate">خروجی هنرجویان</span>
                    <span className="mt-1 block text-gradient-animate text-gradient-animate-warm">
                        دوره انیماتورشو
                    </span>
                </h2>
                <p className="max-w-[257px] text-sm font-medium leading-relaxed text-[#646464]">
                    چند نمونه از تمرین‌ها و پروژه‌هایی که هنرجوها در مسیر دوره
                    ساخته‌اند.
                </p>
            </div>

            <div className="w-full min-w-0">
                <div
                    ref={scrollRef}
                    role="region"
                    aria-label="کارهای هنرجویان"
                    className="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto overscroll-x-contain px-[calc((100%-280px)/2)] pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    style={{ scrollPaddingInline: `calc((100% - ${CARD_WIDTH_PX}px) / 2)` }}
                >
                    {STUDENT_WORKS.map((work, index) => (
                        <StudentWorkCard
                            key={work.id}
                            work={work}
                            isActive={index === activeIndex}
                            cardRef={setCardRef(index)}
                            onOpenVideo={setSelectedWork}
                        />
                    ))}
                </div>

                <div
                    className="mt-5 flex items-center justify-center gap-2"
                    role="tablist"
                    aria-label="صفحه‌بندی کارهای هنرجویان"
                >
                    {STUDENT_WORKS.map((work, index) => (
                        <button
                            key={work.id}
                            type="button"
                            role="tab"
                            aria-selected={index === activeIndex}
                            aria-label={`نمایش کار ${work.studentName}`}
                            onClick={() => scrollToIndex(index)}
                            className={cn(
                                'rounded-full transition-all duration-200',
                                index === activeIndex
                                    ? 'size-2.5 bg-[#2CA3ED]'
                                    : 'size-2 bg-[#d4d4d4]',
                            )}
                        />
                    ))}
                </div>
            </div>

            {selectedWork && (
                <LandingVideoModal
                    videoSrc={selectedWork.videoSrc}
                    ariaLabel={`ویدیو ${selectedWork.projectTitle} — ${selectedWork.studentName}`}
                    onClose={() => setSelectedWork(null)}
                />
            )}
        </section>
    );
}
