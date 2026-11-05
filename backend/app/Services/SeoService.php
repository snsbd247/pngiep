<?php

namespace App\Services;

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

    public function forPath(string $path): array
    {
        $defaults = $this->defaults();
        $meta = [];

        if (Str::startsWith($path, '/admin')) {
            $meta = ['title' => 'Admin | '.$defaults['site_name'], 'robots' => 'noindex, nofollow'];
        } else {
            foreach ($this->resolvers as $pattern => $resolver) {
                if (preg_match($pattern, $path, $matches)) {
                    $meta = $resolver(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)) ?? ['status' => 404, 'title' => 'Page not found | '.$defaults['site_name'], 'robots' => 'noindex'];
                    break;
                }
            }
        }

        $meta = array_merge($defaults, array_filter($meta, fn ($v) => $v !== null && $v !== ''));
        $meta['canonical'] ??= url($path === '/' ? '/' : rtrim($path, '/'));

        return $meta;
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
        if ($analytics = $this->settings->get('analytics_head_code')) {
            // Trusted admin-entered snippet (Settings > SEO, Super Admin / Admin only).
            $tags[] = $analytics;
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
