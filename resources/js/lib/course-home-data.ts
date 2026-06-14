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
    videoPosterUrl?: string | null;
    videoTitle?: string;
    pdfUrl: string | null;
    pdfDownloadName?: string;
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

export type CourseHomeUpdateVisualTheme =
    | 'default'
    | 'purple'
    | 'gold'
    | 'yellow'
    | 'blue'
    | 'green'
    | 'rainbow';

export type CourseHomeUpdatePreview = CourseHomePreviewImage & {
    id: string;
    title: string;
    summary: string;
    type: string;
    typeLabel: string;
    visualTheme: CourseHomeUpdateVisualTheme;
    visualThemeLabel: string;
    publishedAt: string | null;
    publishedAtLabel: string;
    isPinned: boolean;
    body: string | null;
};

export type CourseHomeResourcePreview = CourseHomePreviewImage & {
    id: string;
    title: string;
    description: string;
    type: string;
    typeLabel: string;
    libraryCategory?: string;
    layout?: 'masonry' | 'list';
    fileExtension?: string | null;
    categoryLabel: string | null;
    publishedAt: string | null;
    publishedAtLabel: string;
    actionUrl: string | null;
    actionLabel: string;
    isAvailable: boolean;
    previewUrl?: string | null;
    isVideo?: boolean;
    isGif?: boolean;
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

export type CourseHomeExercisesLatest = {
    title: string;
    status: string;
    statusLabel: string;
    statusTone: 'success' | 'warning' | 'neutral';
};

export type CourseHomeExercisesSummary = {
    total: number;
    pending: number;
    latest: CourseHomeExercisesLatest | null;
    exercisesIndexUrl: string;
    createUrl: string;
};

export type CourseHomeMentorSummary = {
    hasThread: boolean;
    status: string | null;
};

export type CourseHomePreview = {
    updates: CourseHomeUpdatePreview[];
    resources: CourseHomeResourcePreview[];
    resourcesIndexUrl: string;
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
