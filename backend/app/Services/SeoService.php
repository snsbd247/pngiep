<?php

namespace App\Services;

use App\Support\CspNonce;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Builds page metadata for the server-rendered SPA shell.
 *
 * Route resolvers map URL patterns to a callable that returns meta for that page.
 * Phase 1 provides site defaults and static routes; later phases register resolvers
 * for destinations, universities, courses, blogs, etc. via register().
 */
class SeoService
{
    /** @var array<string, callable(array): ?array> regex => resolver */
    private array $resolvers = [];

    public function __construct(private readonly SettingService $settings) {}

    public function register(string $pattern, callable $resolver): void
    {
        $this->resolvers[$pattern] = $resolver;
    }

    public function defaults(): array
    {
        $siteName = $this->settings->get('site_name', config('app.name'));

        return [
            'title' => $this->settings->get('seo_default_title', $siteName),
            'description' => $this->settings->get('seo_default_description', $this->settings->get('site_tagline')),
            'image' => $this->assetUrl($this->settings->get('seo_default_og_image')),
            'site_name' => $siteName,
            'robots' => 'index, follow',
            'type' => 'website',
            'schema' => null,
        ];
    }

    /** Google shows about 60 characters of a title. */
    public const TITLE_MAX = 60;

    /**
     * Section of the site each first URL segment belongs to, for breadcrumbs:
     * segment => [label, URL]. Detail pages (/universities/x) get Home › Section › Page.
     */
    private const SECTIONS = [
        'universities' => ['Universities', '/universities'],
        'courses' => ['Courses', '/courses'],
        'services' => ['Services', '/services'],
        'success-stories' => ['Success Stories', '/success-stories'],
        'blog' => ['Blog & Notice', '/blog'],
        'news' => ['News', '/news'],
        'team' => ['About Us', '/about'],
        'gallery' => ['Gallery', '/gallery'],
    ];

    public function forPath(string $path): array
    {
        $defaults = $this->defaults();
        $notFound = ['status' => 404, 'title' => 'Page not found | '.$defaults['site_name'], 'robots' => 'noindex'];
        $meta = [];
        $matched = false;

        if (Str::startsWith($path, '/admin')) {
            $meta = ['title' => 'Admin | '.$defaults['site_name'], 'robots' => 'noindex, nofollow'];
        } else {
            foreach ($this->resolvers as $pattern => $resolver) {
                if (preg_match($pattern, $path, $matches)) {
                    $matched = true;
                    $meta = $resolver(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)) ?? $notFound;
                    break;
                }
            }
            // File-like URLs no page uses (/wp-login.php, /.env.bak …) are real 404s, not the app shell.
            if (! $matched && preg_match('#\.[A-Za-z0-9]{1,8}$#', $path)) {
                $meta = $notFound;
            }
        }

        $meta = array_merge($defaults, array_filter($meta, fn ($v) => $v !== null && $v !== ''));
        $meta['canonical'] ??= url($path === '/' ? '/' : rtrim($path, '/'));
        $meta['title'] = $this->fitTitle((string) $meta['title'], $defaults['site_name']);

        if ($path !== '/' && ($meta['status'] ?? 200) === 200 && ! str_contains((string) $meta['robots'], 'noindex')) {
            $meta['schema'] = $this->withBreadcrumbs($meta['schema'], $path, $meta['title'], $defaults['site_name']);
        }

        return $meta;
    }

    /** "Page | Full Site Name" → "Page | Short Name" when the full form is longer than Google shows. */
    public function fitTitle(string $title, string $siteName): string
    {
        $short = (string) $this->settings->get('seo_short_name', '');
        $suffix = " | {$siteName}";

        if (! Str::endsWith($title, $suffix) || mb_strlen($title) <= self::TITLE_MAX) {
            return $title;
        }

        $page = Str::beforeLast($title, $suffix);
        $shortened = $short !== '' ? "{$page} | {$short}" : $page;

        // Still too long (e.g. a long course name): the page name alone.
        return mb_strlen($shortened) <= self::TITLE_MAX ? $shortened : $page;
    }

    /** Adds a BreadcrumbList (Home › Section › Page) unless the page already has one. */
    private function withBreadcrumbs(?array $schema, string $path, string $title, string $siteName): ?array
    {
        if ($schema && str_contains(json_encode($schema), '"BreadcrumbList"')) {
            return $schema;
        }

        $name = trim(preg_replace('/\s*\|\s*[^|]*$/', '', $title)) ?: $siteName;
        $segments = explode('/', trim($path, '/'));
        $items = [['Home', '/']];
        if (count($segments) > 1 && isset(self::SECTIONS[$segments[0]])) {
            $items[] = self::SECTIONS[$segments[0]];
        }
        $items[] = [$name, $path];

        $crumbs = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => url($item[1]),
            ])->all(),
        ];

        if (! $schema) {
            return ['@context' => 'https://schema.org'] + $crumbs;
        }

        $graph = $schema['@graph'] ?? [Arr::except($schema, '@context')];

        return ['@context' => 'https://schema.org', '@graph' => [...$graph, $crumbs]];
    }

    public function renderHead(array $meta): string
    {
        $e = fn ($value) => e((string) $value);
        $favicon = $this->assetUrl($this->settings->get('site_favicon'));

        $tags = [
            '<title>'.$e($meta['title']).'</title>',
            '<meta name="description" content="'.$e($meta['description']).'">',
            '<meta name="robots" content="'.$e($meta['robots']).'">',
            '<link rel="canonical" href="'.$e($meta['canonical']).'">',
            '<meta property="og:type" content="'.$e($meta['type']).'">',
            '<meta property="og:site_name" content="'.$e($meta['site_name']).'">',
            '<meta property="og:title" content="'.$e($meta['og_title'] ?? $meta['title']).'">',
            '<meta property="og:description" content="'.$e($meta['og_description'] ?? $meta['description']).'">',
            '<meta property="og:url" content="'.$e($meta['canonical']).'">',
            '<meta name="twitter:card" content="summary_large_image">',
        ];

        if ($meta['image']) {
            $tags[] = '<meta property="og:image" content="'.$e($meta['image']).'">';
        }
        if ($verification = $this->settings->get('google_site_verification')) {
            $tags[] = '<meta name="google-site-verification" content="'.$e($verification).'">';
        }
        if ($meta['schema']) {
            // JSON_HEX_TAG prevents "</script>" breaking out of the block.
            $tags[] = '<script type="application/ld+json">'.json_encode($meta['schema'], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG).'</script>';
        }
        // data-seo lets the SPA remove these once react-helmet takes over the <head>.
        $tags = array_map(fn ($tag) => preg_replace('/^<(meta|link|script)\b/', '<$1 data-seo', $tag), $tags);

        // Not marked data-seo, so the SPA keeps them.
        // Without an uploaded favicon, an empty icon stops browsers requesting /favicon.ico
        // from the web root (outside the app when installed in a sub-folder).
        $tags[] = '<link rel="icon" href="'.($favicon ? $e($favicon) : 'data:,').'">';
        // Admin-entered snippet (Settings > SEO; only a Super Admin can change it). Never on the
        // admin panel itself, where a script could act with the signed-in user's session.
        if (! ($meta['is_admin'] ?? false) && ($analytics = $this->settings->get('analytics_head_code'))) {
            // Its <script> tags get the request's CSP nonce, so the snippet may run (and load more).
            $tags[] = app(CspNonce::class)->addTo($analytics);
        }

        return implode("\n    ", $tags);
    }

    public function assetUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://']) ? $path : asset('storage/'.ltrim($path, '/'));
    }
}
