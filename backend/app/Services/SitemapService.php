<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Course;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Service;
use App\Models\SuccessStory;
use App\Models\University;
use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * sitemap.xml built from published content. Records whose SEO settings say
 * "noindex" are left out. Cached with the public content cache, so any admin
 * change refreshes it automatically.
 */
class SitemapService
{
    /** Built-in list pages whose CMS page slug differs from their URL. */
    private const PAGE_URLS = ['video-gallery' => '/gallery/videos'];

    /** CMS pages that must never be listed. */
    private const EXCLUDED_PAGES = ['appointment'];

    public function xml(): string
    {
        return PublicCache::remember('sitemap.xml', fn () => $this->render($this->entries()), 3600);
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string, priority: string}> */
    public function entries(): Collection
    {
        $entry = fn (string $path, $updatedAt = null, string $priority = '0.6') => [
            'loc' => url($path),
            'lastmod' => $updatedAt?->toAtomString(),
            'priority' => $priority,
        ];
        $indexable = fn (Builder $q) => $q->whereDoesntHave('seo', fn ($s) => $s->where('robots', 'like', '%noindex%'));

        $entries = collect([$entry('/', null, '1.0')]);

        Page::query()->published()->tap($indexable)->whereNotIn('slug', self::EXCLUDED_PAGES)->get(['slug', 'updated_at'])
            ->each(fn ($p) => $entries->push($entry(self::PAGE_URLS[$p->slug] ?? "/{$p->slug}", $p->updated_at, '0.8')));

        $sources = [
            [Destination::class, fn ($r) => "/study-in-{$r->slug}", '0.9'],
            [University::class, fn ($r) => "/universities/{$r->slug}", '0.8'],
            [Course::class, fn ($r) => "/courses/{$r->slug}", '0.7'],
            [Service::class, fn ($r) => "/services/{$r->slug}", '0.8'],
            [SuccessStory::class, fn ($r) => "/success-stories/{$r->slug}", '0.5'],
            [Blog::class, fn ($r) => "/blog/{$r->slug}", '0.6'],
        ];
        foreach ($sources as [$model, $path, $priority]) {
            $model::query()->published()->tap($indexable)->orderBy('id')
                ->chunk(500, fn ($rows) => $rows->each(fn ($r) => $entries->push($entry($path($r), $r->updated_at, $priority))));
        }

        return $entries->unique('loc')->values();
    }

    private function render(Collection $entries): string
    {
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($entries as $u) {
            $xml .= '  <url><loc>'.$e($u['loc']).'</loc>'.($u['lastmod'] ? '<lastmod>'.$e($u['lastmod']).'</lastmod>' : '').'<priority>'.$u['priority'].'</priority></url>'."\n";
        }

        return $xml.'</urlset>'."\n";
    }
}
