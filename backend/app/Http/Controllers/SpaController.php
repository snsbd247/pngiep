<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use App\Services\SitemapService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SpaController extends Controller
{
    private const INDEX = 'spa/index.html';

    public function show(Request $request, SeoService $seo): Response
    {
        $path = public_path(self::INDEX);

        if (! File::exists($path)) {
            return response('Frontend build not found. Run "npm run build" in /frontend.', 503);
        }

        $template = Cache::remember('spa.index.'.File::lastModified($path), now()->addDay(), fn () => File::get($path));
        $meta = $seo->forPath('/'.ltrim($request->path(), '/'));

        // Support installs in a sub-folder (e.g. http://localhost/riths): prefix built asset
        // URLs and tell the SPA its base path (used for routing, API calls and lazy chunks).
        $base = rtrim($request->getBaseUrl(), '/');
        $boot = '<script>window.__APP_BASE__='.json_encode($base, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES).'</script>';
        $html = str_replace(['"/spa/', '<!--app-head-->'], ['"'.$base.'/spa/', $boot."\n    ".$seo->renderHead($meta)], $template);

        return response($html, $meta['status'] ?? 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, private');
    }

    /** Old-style /destinations/{slug} links → canonical /study-in-{slug} (keeps any sub-folder base). */
    public function destinationRedirect(string $slug): RedirectResponse
    {
        return redirect()->to(url("study-in-{$slug}"), 301);
    }

    /** Browsers request /favicon.ico directly; send them to the admin-uploaded favicon. */
    public function favicon(SeoService $seo, SettingService $settings): Response|RedirectResponse
    {
        $url = $seo->assetUrl($settings->get('site_favicon'));

        return $url ? redirect()->away($url, 302) : response('', 204);
    }

    public function sitemap(SitemapService $sitemap): Response
    {
        return response($sitemap->xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    public function robots(Request $request, SettingService $settings): Response
    {
        $custom = $settings->get('robots_txt');
        $base = $request->getBaseUrl(); // "" at a domain root, "/riths" in a sub-folder
        $body = $custom ?: implode("\n", [
            'User-agent: *',
            "Disallow: {$base}/admin",
            "Disallow: {$base}/api/",
            "Disallow: {$base}/appointment/",
            '',
            'Sitemap: '.url('sitemap.xml'),
        ]);

        return response($body."\n", 200)->header('Content-Type', 'text/plain');
    }
}
