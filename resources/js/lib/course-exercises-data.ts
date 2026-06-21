import type { ProfileStatusTone } from '@/lib/profile-data';

export type CourseExerciseAttachment = {
    id: number | null;
    originalName: string;
    sizeBytes: number;
    sizeLabel: string;
    mimeType: string;
    extension: string;
    downloadUrl: string;
    deleteUrl: string | null;
    isDeleted: boolean;
    isLegacy: boolean;
};

export type CourseFeedbackAttachment = {
    id: number;
    originalName: string;
    sizeLabel: string;
    downloadUrl: string;
};

export type CourseExerciseSubmissionItem = {
    id: number;
    title: string;
    description: string | null;
    descriptionPreview: string;
    descriptionHtml: string;
    status: string;
    statusLabel: string;
    statusTone: ProfileStatusTone;
    submissionLink: string | null;
    submissionLinkLabel: string | null;
    attachments: CourseExerciseAttachment[];
    attachment: CourseExerciseAttachment | null;
    feedbackAttachments: CourseFeedbackAttachment[];
    adminFeedback: string | null;
    submittedAt: string | null;
    submittedAtLabel: string;
    reviewedAt: string | null;
    reviewedAtLabel: string;
};

export type CourseExercisesIndexPageProps = {
    submissions: CourseExerciseSubmissionItem[];
    createUrl: string;
};

export type CourseExercisesCreatePageProps = {
    storeUrl: string;
    indexUrl: string;
    maxAttachmentKb: number;
    maxAttachments: number;
};
