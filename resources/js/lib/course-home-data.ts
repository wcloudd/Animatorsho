export type CourseHomeWelcome = {
    displayName: string;
    hasFullAccess: boolean;
};

export type CourseHomeChapter = {
    slug: string;
    title: string;
    chapterNumber: number | null;
    isAccessible: boolean;
    accessLabel: string | null;
};

export type CourseHomeSpotPlayerLicense = {
    packageTitle: string;
    licenseKey: string;
    isFullPackage: boolean;
};

export type CourseHomePageProps = {
    welcome: CourseHomeWelcome;
    chapters: CourseHomeChapter[];
    spotPlayerLicenses: CourseHomeSpotPlayerLicense[];
};
