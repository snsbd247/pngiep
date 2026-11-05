<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\ContentBlocks;
use App\Services\SectionPresenter;
use App\Support\ApiResponse;
use App\Support\PublicCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __construct(private readonly ContentBlocks $blocks, private readonly SectionPresenter $sections) {}

    /** GET /api/v1/pages/{slug}: a published CMS page with its resolved sections. */
    public function show(string $slug): JsonResponse
    {
        $page = PublicCache::remember("page:{$slug}", function () use ($slug) {
            $page = Page::query()->published()->where('slug', $slug)
                ->with(['seo', 'activeSections'])
                ->first();

            if (! $page) {
                return null;
            }

            return $page->only(['id', 'title', 'slug', 'template', 'eyebrow', 'subtitle', 'excerpt', 'banner_image', 'content', 'updated_at'])
                + [
                    'seo' => $page->seo?->only(['title', 'description', 'og_title', 'og_description', 'og_image', 'robots']),
                    'sections' => $this->sections->presentAll($page->activeSections, $page),
                ];
        });

        abort_if($page === null, 404);

        return ApiResponse::success($page);
    }

    /** GET /api/v1/faqs?page_key=contact */
    public function faqs(Request $request): JsonResponse
    {
        $key = $request->validate(['page_key' => ['required', 'string', 'max:50', 'alpha_dash']])['page_key'];

        return ApiResponse::success(PublicCache::remember("faqs:{$key}", fn () => $this->blocks->faqs($key)));
    }
}
