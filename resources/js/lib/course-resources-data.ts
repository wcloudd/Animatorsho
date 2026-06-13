export type CourseResourceItem = {
    id: string;
    title: string;
    description: string;
    type: string;
    typeLabel: string;
    libraryCategory: string;
    layout: 'masonry' | 'list';
    fileExtension: string | null;
    categoryLabel: string | null;
    publishedAt: string | null;
    publishedAtLabel: string;
    actionUrl: string | null;
    actionLabel: string;
    isAvailable: boolean;
    previewUrl: string | null;
    isVideo: boolean;
    isGif: boolean;
    imageUrl: string | null;
    imageAlt: string | null;
};

export type CourseResourceSection = {
    id: string;
    title: string;
    layout: 'masonry' | 'list';
    resources: CourseResourceItem[];
};

export type CourseResourceFilterCategory = {
    id: string;
    label: string;
};

export type CourseResourcesIndexPageProps = {
    categories: CourseResourceFilterCategory[];
    sections: CourseResourceSection[];
    totalCount: number;
};
