<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SeoService
{
    public function __construct(
        private readonly AnimatorshoCatalogService $catalog,
    ) {}

    public function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function absoluteUrl(string $path = '/'): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $normalizedPath = '/'.ltrim($path, '/');

        if ($normalizedPath === '/') {
            return $this->appUrl();
        }

        return $this->appUrl().$normalizedPath;
    }

    public function robotsTxt(): string
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
        ];

        foreach (config('seo.disallow_paths', []) as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = 'Sitemap: '.$this->absoluteUrl('/sitemap.xml');

        return implode("\n", $lines)."\n";
    }

    public function sitemapXml(): string
    {
        $urls = [];

        foreach (config('seo.sitemap_routes', []) as $routeName) {
            if (! Route::has($routeName)) {
                continue;
            }

            $urls[] = [
                'loc' => $this->absoluteUrl(route($routeName, [], false)),
                'lastmod' => now()->toAtomString(),
            ];
        }

        $entries = collect($urls)
            ->map(function (array $url): string {
                $location = htmlspecialchars($url['loc'], ENT_XML1);

                return implode("\n", [
                    '  <url>',
                    '    <loc>'.$location.'</loc>',
                    '    <lastmod>'.$url['lastmod'].'</lastmod>',
                    '  </url>',
                ]);
            })
            ->implode("\n");

        return implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            $entries,
            '</urlset>',
            '',
        ]);
    }

    public function shouldNoIndex(Request $request): bool
    {
        $route = $request->route();

        if ($route === null) {
            return false;
        }

        $routeName = $route->getName();

        if ($routeName === null) {
            return false;
        }

        foreach (config('seo.noindex_route_names', []) as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function courseStructuredDataForHome(): ?array
    {
        $catalog = $this->catalog->catalogForInertia();

        if ($catalog === null) {
            return null;
        }

        $course = $this->catalog->findPublishedCourse();

        if ($course === null) {
            return null;
        }

        $fullPackage = $catalog['fullPackage'];
        $priceToman = (int) $fullPackage['priceToman'];

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $course->title,
            'description' => 'دوره جامع آموزش ساخت انیمیشن از صفر تا اولین خروجی، با مسیر گام‌به‌گام و پشتیبانی.',
            'url' => $this->absoluteUrl('/'),
            'provider' => [
                '@type' => 'Organization',
                'name' => (string) config('seo.organization.name'),
                'url' => $this->appUrl(),
            ],
            'inLanguage' => 'fa',
            'offers' => [
                '@type' => 'Offer',
                'url' => $this->absoluteUrl(route('checkout', absolute: false)),
                'priceCurrency' => 'IRR',
                'price' => (string) ($priceToman * 10),
                'availability' => 'https://schema.org/InStock',
            ],
        ];

        return $structuredData;
    }

    /**
     * @return array<string, mixed>
     */
    public function organizationStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => (string) config('seo.organization.name'),
            'alternateName' => (string) config('seo.organization.alternate_name'),
            'url' => $this->appUrl(),
            'logo' => $this->absoluteUrl((string) config('seo.default_og_image')),
        ];
    }

    /**
     * @return array{
     *     organization: array<string, mixed>,
     *     course: array<string, mixed>|null,
     *     ogImage: string
     * }
     */
    public function homePageSeoProps(): array
    {
        return [
            'organization' => $this->organizationStructuredData(),
            'course' => $this->courseStructuredDataForHome(),
            'ogImage' => $this->absoluteUrl((string) config('seo.default_og_image')),
        ];
    }
}
