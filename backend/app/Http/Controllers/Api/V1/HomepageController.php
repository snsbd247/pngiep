<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Services\ContentBlocks;
use App\Support\ApiResponse;
use App\Support\PublicCache;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/homepage: active sections in admin-defined order, each with the
 * records it displays. Cached until any content changes.
 */
class HomepageController extends Controller
{
    public function __invoke(ContentBlocks $blocks): JsonResponse
    {
        $sections = PublicCache::remember('homepage', function () use ($blocks) {
            return HomepageSection::query()->active()->ordered()->get()
                ->map(function (HomepageSection $section) use ($blocks) {
                    $limit = $section->items_limit;

                    $items = match ($section->key) {
                        'hero' => ['slides' => $blocks->heroSlides()],
                        'statistics' => ['statistics' => $blocks->statistics('home')],
                        'destinations' => ['destinations' => $blocks->destinations($limit ?? 5)],
                        'services' => ['services' => $blocks->services($limit ?? 8)],
                        'universities' => ['universities' => $blocks->universities($limit ?? 12)],
                        'success_stories' => ['stories' => $blocks->successStories($limit ?? 6)],
                        'testimonials' => ['testimonials' => $blocks->testimonials($limit ?? 6)],
                        'gallery' => ['photos' => $blocks->photos($limit ?? 4), 'videos' => $blocks->videos(2)],
                        'latest_news' => ['posts' => $blocks->latestPosts($limit ?? 3)],
                        'partners' => ['partners' => $blocks->partners()],
                        default => [],
                    };

                    return $section->only(['key', 'eyebrow', 'title', 'subtitle', 'image', 'button_text', 'button_url', 'data']) + $items;
                })
                ->values()
                ->all();
        });

        return ApiResponse::success(['sections' => $sections]);
    }
}
