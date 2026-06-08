export type SeoRobotsDirective = 'index,follow' | 'noindex,nofollow';

export type SeoOpenGraph = {
    title?: string;
    description?: string;
    url?: string;
    type?: string;
    image?: string;
};

export type SeoHeadProps = {
    title?: string;
    description?: string;
    canonical?: string;
    robots?: SeoRobotsDirective;
    openGraph?: SeoOpenGraph;
    jsonLd?: Record<string, unknown> | Array<Record<string, unknown>>;
};

export type HomeSeoProps = {
    organization: Record<string, unknown>;
    course: Record<string, unknown> | null;
    ogImage: string;
};

export type SharedPageProps = {
    appUrl: string;
    name: string;
};
