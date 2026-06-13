export type CourseHomeWelcome = {
    displayName: string;
    firstName: string;
};

export type CourseHomeProgress = {
    level: number;
    totalXp: number;
    progressPercent: number;
    xpToNextLevel: number;
};

export type CourseHomeOnboarding = {
    title: string;
    heading: string;
    description: string;
    imageUrl: string | null;
    imageAlt: string;
    videoUrl: string | null;
    pdfUrl: string | null;
    videoGuideLabel: string;
    pdfGuideLabel: string;
};

export type CourseHomeSectionVisual = {
    imageUrl: string | null;
    imageAlt: string;
    placeholderTitle: string;
    placeholderDescription: string | null;
};

export type CourseHomeSectionVisuals = {
    exercises: CourseHomeSectionVisual;
    mentor: CourseHomeSectionVisual;
    resources: CourseHomeSectionVisual;
    medals: CourseHomeSectionVisual;
    updates: CourseHomeSectionVisual;
};

export type CourseHomePreviewImage = {
    imageUrl: string | null;
    imageAlt: string | null;
};

export type CourseHomeUpdatePreview = CourseHomePreviewImage & {
    id: string;
    title: string;
    summary: string;
    type: string;
    typeLabel: string;
    publishedAtLabel: string;
};

export type CourseHomeResourcePreview = CourseHomePreviewImage & {
    id: string;
    title: string;
    description: string;
    type: string;
    typeLabel: string;
};

export type CourseHomeMedalItem = {
    slug: string;
    title: string;
};

export type CourseHomeMedalsPreview = {
    earned: CourseHomeMedalItem[];
    locked: CourseHomeMedalItem[];
    totalAvailable: number;
};

export type CourseHomeExercisesSummary = {
    total: number;
    pending: number;
};

export type CourseHomeMentorSummary = {
    hasThread: boolean;
    status: string | null;
};

export type CourseHomePreview = {
    updates: CourseHomeUpdatePreview[];
    resources: CourseHomeResourcePreview[];
    notificationsUnread: number;
    exercisesSummary: CourseHomeExercisesSummary;
    mentorSummary: CourseHomeMentorSummary;
    medals: CourseHomeMedalsPreview;
    sectionVisuals: CourseHomeSectionVisuals;
};

export type CourseHomePageProps = {
    welcome: CourseHomeWelcome;
    progress: CourseHomeProgress;
    onboarding: CourseHomeOnboarding;
    preview: CourseHomePreview;
};
