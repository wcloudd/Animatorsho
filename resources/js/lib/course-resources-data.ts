export type CourseResourceItem = {
    id: string;
    title: string;
    description: string;
    type: string;
    typeLabel: string;
    categoryLabel: string | null;
    publishedAt: string | null;
    publishedAtLabel: string;
    actionUrl: string | null;
    actionLabel: string;
    isAvailable: boolean;
};

export type CourseResourceGroup = {
    id: string;
    title: string;
    resources: CourseResourceItem[];
};

export type CourseResourcesIndexPageProps = {
    groups: CourseResourceGroup[];
    totalCount: number;
};
