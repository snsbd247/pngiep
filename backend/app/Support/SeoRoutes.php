<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\Course;
use App\Models\Destination;
use App\Models\News;
use App\Models\Page;
use App\Models\Service;
use App\Models\SuccessStory;
use App\Models\TeamMember;
use App\Models\University;
use App\Services\SeoService;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Server-side SEO resolvers for public URLs (first match wins, so specific
 * patterns are registered before generic ones). Each phase adds its routes here.
 */
class SeoRoutes
{
    /**
     * Built-in SPA routes whose content arrives in later phases. Listed so they render
     * with a proper title (HTTP 200) instead of being treated as unknown CMS slugs.
     */
    public const STATIC_ROUTES = [
        'destinations' => 'Study Destinations',
        'universities' => 'Universities',
        'courses' => 'Courses',
        'services' => 'Our Services',
        'success-stories' => 'Success Stories',
        'gallery' => 'Gallery',
        'blog' => 'Blog & Notice',
        'news' => 'News',
        'contact' => 'Contact Us',
        'book-appointment' => 'Book Appointment',
        'search' => 'Search',
        'apply' => 'Apply Now',
    ];

    public static function register(SeoService $seo): void
    {
        $siteName = fn () => $seo->defaults()['site_name'];

        // Homepage: organisation + site search for rich results.
        $seo->register('#^/$#', function () use ($seo, $siteName) {
            $settings = app(SettingService::class);
            $sameAs = collect(['facebook', 'linkedin', 'instagram', 'youtube', 'tiktok', 'x'])
                ->map(fn ($k) => $settings->get("social_{$k}"))
                // Placeholder links to a network's home page are not profiles.
                ->filter(fn ($u) => $u && trim((string) parse_url($u, PHP_URL_PATH), '/') !== '')->values()->all();

            return ['schema' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    // An education consultancy with an office: EducationalOrganization for what it
                    // is, LocalBusiness so it can appear in local results ("study abroad Port Moresby").
                    array_filter([
                        '@type' => ['EducationalOrganization', 'LocalBusiness'],
                        '@id' => url('/').'#organization',
                        'name' => $siteName(),
                        'url' => url('/'),
                        'logo' => $seo->assetUrl($settings->get('site_logo')),
                        'image' => $seo->assetUrl($settings->get('seo_default_og_image') ?: $settings->get('site_logo')),
                        'description' => $settings->get('seo_default_description') ?: $settings->get('site_tagline'),
                        'email' => $settings->get('contact_email'),
                        'telephone' => $settings->get('contact_phone'),
                        'address' => self::postalAddress($settings),
                        'areaServed' => ($country = strtoupper((string) $settings->get('site_country')))
                            ? ['@type' => 'Country', 'name' => class_exists(\Locale::class) ? (\Locale::getDisplayRegion("-{$country}", 'en') ?: $country) : $country]
                            : null,
                        'sameAs' => $sameAs ?: null,
                    ]),
                    [
                        '@type' => 'WebSite',
                        'name' => $siteName(),
                        'url' => url('/'),
                        'publisher' => ['@id' => url('/').'#organization'],
                        'potentialAction' => [
                            '@type' => 'SearchAction',
                            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/search').'?q={search_term_string}'],
                            'query-input' => 'required name=search_term_string',
                        ],
                    ],
                ],
            ]];
        });

        $seo->register('#^/study-in-(?<slug>[a-z0-9-]+)$#', function (array $p) use ($seo, $siteName) {
            $d = Destination::query()->published()->where('slug', $p['slug'])->with('seo')->first();

            return $d ? self::meta($seo, $d, "Study in {$d->name} | {$siteName()}", $d->short_description ?: $d->tagline, $d->hero_image ?: $d->thumbnail,
                self::breadcrumbs($seo, [['Destinations', '/destinations'], [$d->name, "/study-in-{$d->slug}"]])) : null;
        });

        $seo->register('#^/universities/(?<slug>[a-z0-9-]+)$#', function (array $p) use ($seo, $siteName) {
            $u = University::query()->published()->where('slug', $p['slug'])->with(['seo', 'destination:id,name'])->first();
            if (! $u) {
                return null;
            }
            $schema = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'CollegeOrUniversity',
                'name' => $u->name,
                'url' => url("/universities/{$u->slug}"),
                'logo' => $seo->assetUrl($u->logo),
                'sameAs' => $u->website,
                'foundingDate' => $u->established_year ? (string) $u->established_year : null,
                'address' => array_filter(['@type' => 'PostalAddress', 'addressLocality' => $u->city, 'addressCountry' => $u->destination?->name]),
            ]);

            return self::meta($seo, $u, "{$u->name} | {$siteName()}", $u->short_description, $u->cover_image ?: $u->thumbnail, $schema);
        });

        $seo->register('#^/courses/(?<slug>[a-z0-9-]+)$#', function (array $p) use ($seo, $siteName) {
            $c = Course::query()->published()->where('slug', $p['slug'])->with(['seo', 'university:id,name,slug,website'])->first();
            if (! $c) {
                return null;
            }
            $schema = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Course',
                'name' => $c->name,
                'description' => $c->short_description ?: Str::limit(strip_tags((string) $c->description), 300),
                'url' => url("/courses/{$c->slug}"),
                'image' => $seo->assetUrl($c->image),
                'provider' => array_filter([
                    '@type' => 'CollegeOrUniversity',
                    'name' => $c->university?->name,
                    'url' => $c->university ? url("/universities/{$c->university->slug}") : null,
                    'sameAs' => $c->university?->website,
                ]),
                'educationalCredentialAwarded' => $c->degree_type,
                // ISO 8601 duration, e.g. 36 months → P36M.
                'timeRequired' => $c->duration_months ? "P{$c->duration_months}M" : null,
                // Tuition per fee period (usually per year), as entered in the admin panel.
                'offers' => ($c->tuition_fee && $c->currency_code) ? array_filter([
                    '@type' => 'Offer',
                    'category' => 'Paid',
                    'price' => (string) (float) $c->tuition_fee,
                    'priceCurrency' => strtoupper($c->currency_code),
                    'description' => $c->fee_period ? "Tuition fee per {$c->fee_period}" : null,
                ]) : null,
            ]);

            return self::meta($seo, $c, "{$c->name} at {$c->university?->name} | {$siteName()}", $c->short_description, $c->image, $schema);
        });

        $seo->register('#^/services/(?<slug>[a-z0-9-]+)$#', function (array $p) use ($seo, $siteName) {
            $s = Service::query()->published()->where('slug', $p['slug'])->with('seo')->first();
            if (! $s) {
                return null;
            }
            $schema = ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $s->title, 'description' => $s->short_description,
                'provider' => ['@type' => 'EducationalOrganization', 'name' => $siteName()]];

            return self::meta($seo, $s, "{$s->title} | {$siteName()}", $s->short_description, $s->image, $schema);
        });

        $seo->register('#^/success-stories/(?<slug>[a-z0-9-]+)$#', function (array $p) use ($seo, $siteName) {
            $s = SuccessStory::query()->published()->where('slug', $p['slug'])->with(['seo', 'university:id,name'])->first();
            if (! $s) {
                return null;
            }
            $university = $s->university?->name ?? $s->university_name;

            return self::meta($seo, $s, "{$s->student_name}'s Success Story | {$siteName()}", $s->excerpt ?: $s->quote ?: "{$s->student_name} – {$s->course_name}, {$university}", $s->cover_image ?: $s->photo,
                self::breadcrumbs($seo, [['Success Stories', '/success-stories'], [$s->student_name, "/success-stories/{$s->slug}"]]));
        });

        $seo->register('#^/blog/(?<slug>[a-z0-9-]+)$#', function (array $p) use ($seo, $siteName) {
            $b = Blog::query()->published()->where('slug', $p['slug'])->with('seo')->first();
            if (! $b) {
                return null;
            }
            $image = $seo->assetUrl($b->seo?->og_image ?: $b->featured_image);
            $schema = array_filter([
                '@context' => 'https://schema.org',
                '@type' => $b->type === 'notice' ? 'NewsArticle' : 'BlogPosting',
                'headline' => Str::limit($b->title, 110, ''),
                'description' => $b->excerpt,
                'image' => $image,
                'datePublished' => $b->published_at?->toIso8601String(),
                'dateModified' => $b->updated_at?->toIso8601String(),
                'author' => ['@type' => 'Person', 'name' => $b->author_name ?: $siteName()],
                'publisher' => ['@type' => 'Organization', 'name' => $siteName()],
                'mainEntityOfPage' => url("/blog/{$b->slug}"),
            ]);

            return self::meta($seo, $b, "{$b->title} | {$siteName()}", $b->excerpt ?: strip_tags((string) $b->content), $b->featured_image, $schema) + ['type' => 'article'];
        });

        $seo->register('#^/team/(?<slug>[a-z0-9-]+)$#', function (array $p) use ($seo, $siteName) {
            $m = TeamMember::query()->active()->where('slug', $p['slug'])->with('seo')->first();
            if (! $m) {
                return null;
            }
            $schema = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => $m->name,
                'jobTitle' => $m->designation,
                'description' => $m->bio,
                'image' => $seo->assetUrl($m->photo),
                'url' => url("/team/{$m->slug}"),
                'worksFor' => ['@type' => 'Organization', 'name' => $siteName()],
                'sameAs' => array_values(array_filter([$m->social_links['linkedin'] ?? null, $m->social_links['facebook'] ?? null])) ?: null,
            ]);
            $title = trim($m->name.($m->designation ? " – {$m->designation}" : ''));

            return self::meta($seo, $m, "{$title} | {$siteName()}", $m->bio ?: strip_tags((string) ($m->message ?: $m->profile)), $m->photo, $schema) + ['type' => 'profile'];
        });

        $seo->register('#^/news/(?<slug>[a-z0-9-]+)$#', function (array $p) use ($seo, $siteName) {
            $n = News::query()->published()->where('slug', $p['slug'])->with('seo')->first();
            if (! $n) {
                return null;
            }
            $schema = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'NewsArticle',
                'headline' => Str::limit($n->title, 110, ''),
                'description' => $n->excerpt,
                'image' => $seo->assetUrl($n->seo?->og_image ?: $n->featured_image),
                'datePublished' => $n->published_at?->toIso8601String(),
                'dateModified' => $n->updated_at?->toIso8601String(),
                'author' => ['@type' => 'Person', 'name' => $n->author_name ?: $siteName()],
                'publisher' => ['@type' => 'Organization', 'name' => $siteName()],
                'mainEntityOfPage' => url("/news/{$n->slug}"),
            ]);

            return self::meta($seo, $n, "{$n->title} | {$siteName()}", $n->excerpt ?: strip_tags((string) $n->content), $n->featured_image, $schema) + ['type' => 'article'];
        });

        // Single-segment URLs: CMS pages (about, privacy-policy, built-in list pages …).
        $seo->register('#^/(?<slug>[a-z0-9-]+)$#', function (array $p) use ($seo, $siteName) {
            $page = Page::query()->published()->where('slug', $p['slug'])->with('seo')->first();

            if ($page) {
                $title = str_replace('*', '', $page->title);
                $description = $page->excerpt ?: $page->subtitle ?: Str::limit(strip_tags((string) $page->content), 160);

                return self::meta($seo, $page, "{$title} | {$siteName()}", $description, $page->banner_image);
            }

            if (isset(self::STATIC_ROUTES[$p['slug']])) {
                // Internal search results are thin, duplicate pages: crawl their links, don't index them.
                return ['title' => self::STATIC_ROUTES[$p['slug']].' | '.$siteName()]
                    + ($p['slug'] === 'search' ? ['robots' => 'noindex, follow'] : []);
            }

            return null; // → 404
        });

        // Private manage-appointment links must never be indexed.
        $seo->register('#^/appointment/[A-Za-z0-9]{64}$#', fn () => ['title' => 'Manage Appointment | '.$siteName(), 'robots' => 'noindex, nofollow']);

        // Video gallery sub-page.
        $seo->register('#^/gallery/videos$#', function () use ($seo, $siteName) {
            $page = Page::query()->published()->where('slug', 'video-gallery')->with('seo')->first();

            return $page ? self::meta($seo, $page, str_replace('*', '', $page->title).' | '.$siteName(), $page->excerpt, $page->banner_image)
                : ['title' => 'Video Gallery | '.$siteName()];
        });
    }

    /** Record-specific SEO overrides fall back to the record's own fields. */
    private static function meta(SeoService $seo, Model $record, string $title, ?string $description, ?string $image, ?array $schema = null): array
    {
        $custom = $record->seo;

        return [
            'title' => $custom?->title ?: $title,
            'description' => $custom?->description ?: Str::limit(strip_tags((string) $description), 160),
            'og_title' => $custom?->og_title,
            'og_description' => $custom?->og_description,
            'image' => $seo->assetUrl($custom?->og_image ?: $image),
            'robots' => $custom?->robots,
            'canonical' => $custom?->canonical_url,
            'schema' => $schema,
        ];
    }

    /**
     * PostalAddress from Settings › Contact: the full address as street address, the city from
     * "Short location" (text before the first comma) and the country code from Settings › General.
     */
    private static function postalAddress(SettingService $settings): ?array
    {
        $address = trim(preg_replace('/\s*\R\s*/', ', ', (string) $settings->get('contact_address')), " ,");
        if ($address === '') {
            return null;
        }

        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $address,
            'addressLocality' => trim(Str::before((string) $settings->get('contact_location'), ',')) ?: null,
            'addressCountry' => strtoupper((string) $settings->get('site_country')) ?: null,
        ]);
    }

    private static function breadcrumbs(SeoService $seo, array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect([['Home', '/'], ...$items])->values()->map(fn ($item, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => url($item[1]),
            ])->all(),
        ];
    }
}
