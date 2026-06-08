import { Head } from '@inertiajs/react';
import type { SeoHeadProps } from '@/types/seo';

function JsonLdScript({
    jsonLd,
}: {
    jsonLd: NonNullable<SeoHeadProps['jsonLd']>;
}) {
    const payload = Array.isArray(jsonLd) ? jsonLd : [jsonLd];

    return (
        <>
            {payload.map((entry, index) => (
                <script
                    key={`json-ld-${index}`}
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{
                        __html: JSON.stringify(entry),
                    }}
                />
            ))}
        </>
    );
}

export function SeoHead({
    title,
    description,
    canonical,
    robots = 'index,follow',
    openGraph,
    jsonLd,
}: SeoHeadProps) {
    const resolvedOgTitle = openGraph?.title ?? title;
    const resolvedOgDescription = openGraph?.description ?? description;
    const resolvedOgUrl = openGraph?.url ?? canonical;

    return (
        <Head title={title}>
            {description ? (
                <meta head-key="description" name="description" content={description} />
            ) : null}

            {canonical ? (
                <link head-key="canonical" rel="canonical" href={canonical} />
            ) : null}

            <meta head-key="robots" name="robots" content={robots} />

            {resolvedOgTitle ? (
                <meta
                    head-key="og:title"
                    property="og:title"
                    content={resolvedOgTitle}
                />
            ) : null}

            {resolvedOgDescription ? (
                <meta
                    head-key="og:description"
                    property="og:description"
                    content={resolvedOgDescription}
                />
            ) : null}

            {resolvedOgUrl ? (
                <meta head-key="og:url" property="og:url" content={resolvedOgUrl} />
            ) : null}

            {openGraph?.type ? (
                <meta
                    head-key="og:type"
                    property="og:type"
                    content={openGraph.type}
                />
            ) : null}

            {openGraph?.image ? (
                <meta
                    head-key="og:image"
                    property="og:image"
                    content={openGraph.image}
                />
            ) : null}

            {jsonLd ? <JsonLdScript jsonLd={jsonLd} /> : null}
        </Head>
    );
}

export function NoIndexSeoHead() {
    return <SeoHead robots="noindex,nofollow" />;
}
