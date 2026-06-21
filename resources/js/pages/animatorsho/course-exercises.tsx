import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, ClipboardList, Download, ExternalLink } from 'lucide-react';
import { SafeStoryText } from '@/components/course/safe-story-text';
import { PageContainer } from '@/components/page-container';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type { CourseExercisesIndexPageProps } from '@/lib/course-exercises-data';
import { cn } from '@/lib/utils';

function feedbackToneClassName(status: string): string {
    if (status === 'approved') {
        return 'bg-green-soft text-green ring-green/15';
    }

    if (status === 'needs_revision') {
        return 'bg-gold-soft text-gold ring-gold/20';
    }

    return 'bg-purple-soft text-muted ring-purple/10';
}

export default function CourseExercises({
    submissions,
    createUrl,
}: CourseExercisesIndexPageProps) {
    const hasSubmissions = submissions.length > 0;

    return (
        <>
            <Head title="تمرین‌های من" />
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
                        <span className="inline-flex items-center gap-1.5 rounded-pill bg-gold-soft px-3 py-1 text-[11px] font-bold text-gold ring-1 ring-gold/20">
                            <ClipboardList className="size-3.5" />
                            تمرین‌های من
                        </span>
                        <h1 className="font-display text-[1.625rem] leading-tight font-bold text-text">
                            تمرین‌های من
                        </h1>
                        <p className="max-w-[320px] text-sm font-medium leading-relaxed text-muted">
                            تمرین‌های انیمیشنت را بفرست و بازخورد استاد را
                            دریافت کن
                        </p>
                    </header>

                    <Link
                        href={createUrl}
                        className="inline-flex items-center justify-center rounded-pill bg-purple px-4 py-3 text-sm font-bold text-white shadow-soft transition-colors hover:bg-purple/90"
                    >
                        ارسال تمرین جدید
                    </Link>

                    {hasSubmissions ? (
                        <ul className="flex flex-col gap-3">
                            {submissions.map((submission) => {
                                const activeAttachments =
                                    submission.attachments.filter(
                                        (attachment) =>
                                            !attachment.isDeleted &&
                                            attachment.downloadUrl !== '',
                                    );

                                return (
                                    <li
                                        key={submission.id}
                                        className="flex flex-col gap-3 rounded-[28px] bg-surface px-4 py-4 shadow-soft ring-1 ring-border"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="flex min-w-0 flex-col gap-1">
                                                <h2 className="text-sm font-bold text-text">
                                                    {submission.title}
                                                </h2>
                                                <p className="text-xs font-medium text-muted">
                                                    {submission.submittedAtLabel}
                                                </p>
                                            </div>
                                            <ProfileStatusBadge
                                                tone={submission.statusTone}
                                            >
                                                {submission.statusLabel}
                                            </ProfileStatusBadge>
                                        </div>

                                        {submission.descriptionPreview ? (
                                            <p className="text-sm font-medium leading-relaxed text-muted">
                                                {submission.descriptionPreview}
                                            </p>
                                        ) : submission.descriptionHtml ? (
                                            <SafeStoryText
                                                html={
                                                    submission.descriptionHtml
                                                }
                                                className="text-sm font-medium leading-relaxed text-muted [&_p]:mb-2 [&_ul]:list-disc [&_ul]:ps-5 [&_ol]:list-decimal [&_ol]:ps-5"
                                            />
                                        ) : null}

                                        {submission.submissionLink ? (
                                            <a
                                                href={submission.submissionLink}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-1 self-start text-xs font-bold text-purple"
                                            >
                                                <ExternalLink className="size-3.5" />
                                                {submission.submissionLinkLabel}
                                            </a>
                                        ) : null}

                                        {activeAttachments.length > 0 ? (
                                            <ul className="flex flex-col gap-2">
                                                {activeAttachments.map(
                                                    (attachment) => (
                                                        <li
                                                            key={
                                                                attachment.id ??
                                                                attachment.originalName
                                                            }
                                                        >
                                                            <a
                                                                href={
                                                                    attachment.downloadUrl
                                                                }
                                                                className="flex items-center justify-between gap-3 rounded-2xl bg-bg px-3 py-2.5 ring-1 ring-border/70"
                                                            >
                                                                <span className="flex min-w-0 flex-col gap-0.5 text-start">
                                                                    <span className="truncate text-xs font-bold text-text">
                                                                        {
                                                                            attachment.originalName
                                                                        }
                                                                    </span>
                                                                    <span className="text-[11px] font-medium text-muted">
                                                                        {
                                                                            attachment.sizeLabel
                                                                        }
                                                                    </span>
                                                                </span>
                                                                <span className="inline-flex shrink-0 items-center gap-1 text-xs font-bold text-purple">
                                                                    <Download className="size-3.5" />
                                                                    دانلود
                                                                </span>
                                                            </a>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        ) : submission.submissionLinkLabel &&
                                          !submission.submissionLink ? (
                                            <p className="text-xs font-bold text-muted">
                                                {
                                                    submission.submissionLinkLabel
                                                }
                                            </p>
                                        ) : null}

                                        {submission.adminFeedback ? (
                                            <div
                                                className={cn(
                                                    'rounded-2xl px-3 py-3 text-sm font-medium leading-relaxed ring-1',
                                                    feedbackToneClassName(
                                                        submission.status,
                                                    ),
                                                )}
                                            >
                                                <p className="mb-1 text-xs font-bold">
                                                    بازخورد استاد
                                                </p>
                                                <p>
                                                    {submission.adminFeedback}
                                                </p>
                                                {submission.reviewedAtLabel !==
                                                '—' ? (
                                                    <p className="mt-2 text-[11px] font-medium opacity-80">
                                                        {
                                                            submission.reviewedAtLabel
                                                        }
                                                    </p>
                                                ) : null}
                                            </div>
                                        ) : null}
                                    </li>
                                );
                            })}
                        </ul>
                    ) : (
                        <p className="rounded-2xl bg-surface px-4 py-4 text-sm font-medium leading-relaxed text-muted shadow-soft ring-1 ring-border">
                            هنوز تمرینی ارسال نکرده‌ای.
                        </p>
                    )}
                </div>
            </PageContainer>
        </>
    );
}
