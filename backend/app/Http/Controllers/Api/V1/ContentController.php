<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Destination;
use App\Models\GalleryAlbum;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Models\GalleryVideo;
use App\Models\SuccessStory;
use App\Services\AdminNotifier;
use App\Support\ApiResponse;
use App\Support\PublicCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Public success stories, photo / video gallery and blog & notices.
 */
class ContentController extends Controller
{
    private const STORY_CARD = ['id', 'student_name', 'slug', 'photo', 'destination_id', 'university_id', 'course_id', 'university_name',
        'course_name', 'study_level', 'year', 'quote', 'excerpt', 'youtube_id'];

    private const POST_CARD = ['id', 'blog_category_id', 'destination_id', 'type', 'title', 'slug', 'featured_image', 'excerpt', 'published_at', 'views_count', 'author_name'];

    // ------------------------------------------------------------ success stories

    public function stories(Request $request): JsonResponse
    {
        $params = $request->validate([
            'destination' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $page = SuccessStory::query()->published()
            ->with(['destination:id,name,slug,country_code', 'university:id,name,slug', 'course:id,name,slug'])
            ->when($params['destination'] ?? null, fn ($q, $d) => $q->whereHas('destination', fn ($w) => $w->where('slug', $d)))
            ->when($params['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('student_name', 'like', "%{$s}%")
                ->orWhere('university_name', 'like', "%{$s}%")->orWhere('course_name', 'like', "%{$s}%")
                ->orWhereHas('university', fn ($u) => $u->where('name', 'like', "%{$s}%"))))
            ->orderByDesc('is_featured')->ordered()
            ->paginate($params['per_page'] ?? 9, self::STORY_CARD);

        return $this->paginated($page, [
            'destinations' => PublicCache::remember('facets:stories', fn () => Destination::query()->published()->ordered()
                ->withCount(['successStories' => fn ($q) => $q->published()])->get(['id', 'name', 'slug', 'country_code'])
                ->filter(fn ($d) => $d->success_stories_count > 0)
                ->map(fn ($d) => ['value' => $d->slug, 'label' => $d->name, 'country_code' => $d->country_code, 'count' => $d->success_stories_count])->values()),
            'videos' => PublicCache::remember('stories:videos', fn () => SuccessStory::query()->published()->whereNotNull('youtube_id')
                ->with(['university:id,name'])->ordered()->limit(6)->get(['id', 'student_name', 'slug', 'university_id', 'university_name', 'youtube_id', 'photo'])),
        ]);
    }

    public function story(string $slug): JsonResponse
    {
        $data = PublicCache::remember("story:{$slug}", function () use ($slug) {
            $story = SuccessStory::query()->published()->where('slug', $slug)
                ->with([
                    'seo',
                    'destination:id,name,slug,country_code',
                    'university:id,destination_id,name,slug,logo,thumbnail,cover_image,short_description,highlights,world_ranking',
                    'course:id,name,slug',
                ])->first();

            if (! $story) {
                return null;
            }

            return $story->makeHidden(['sort_order', 'status', 'deleted_at', 'created_at', 'submitter_email'])->toArray() + [
                'seo' => $story->seo?->only(['title', 'description', 'og_title', 'og_description', 'og_image', 'robots']),
                'related' => SuccessStory::query()->published()->whereKeyNot($story->id)
                    ->orderByRaw('destination_id = ? desc', [$story->destination_id ?? 0])->ordered()->limit(4)
                    ->with(['destination:id,name,country_code', 'university:id,name'])->get(self::STORY_CARD),
            ];
        });

        abort_if($data === null, 404);

        return ApiResponse::success($data);
    }

    /** POST /success-stories: a visitor shares their story; saved as a draft for admin review. */
    public function submitStory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'destination' => ['nullable', 'string', 'max:100'],
            'university' => ['nullable', 'string', 'max:190'],
            'course' => ['nullable', 'string', 'max:190'],
            'story' => ['required', 'string', 'min:20', 'max:5000'],
            'website' => ['prohibited'],
        ], ['website.prohibited' => 'Your story could not be sent.']);

        $story = SuccessStory::query()->create([
            'student_name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'submitter_email' => strtolower($data['email']),
            'destination_id' => isset($data['destination']) ? Destination::query()->where('slug', $data['destination'])->value('id') : null,
            'university_name' => $data['university'] ?? null,
            'course_name' => $data['course'] ?? null,
            // Plain text only: escape and keep paragraphs.
            'story' => collect(preg_split('/\R{2,}/', trim($data['story'])))->map(fn ($p) => '<p>'.nl2br(e(trim($p))).'</p>')->implode(''),
            'excerpt' => Str::limit($data['story'], 200),
            'status' => ContentStatus::Draft->value,
        ]);

        app(AdminNotifier::class)->notify('story', "Story submitted by {$story->student_name}", 'Waiting for review', "/admin/content/success-stories/{$story->id}");

        return ApiResponse::success(null, 'Thank you for sharing your story! Our team will review it shortly.', 201);
    }

    // ------------------------------------------------------------ gallery

    public function photos(Request $request): JsonResponse
    {
        $params = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $page = GalleryAlbum::query()->published()
            ->whereHas('images', fn ($q) => $q->published())
            ->with('category:id,name,slug')
            ->withCount(['images' => fn ($q) => $q->published()])
            ->when($params['category'] ?? null, fn ($q, $c) => $q->whereHas('category', fn ($w) => $w->where('slug', $c)))
            ->when($params['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")))
            ->orderByDesc('is_featured')->orderByDesc('event_date')->ordered()
            ->paginate($params['per_page'] ?? 12, ['id', 'gallery_category_id', 'title', 'slug', 'description', 'cover_image', 'event_date']);

        // Albums without a cover use their first photo.
        $covers = GalleryImage::query()->published()->whereIn('gallery_album_id', $page->getCollection()->pluck('id'))
            ->orderBy('sort_order')->get(['gallery_album_id', 'image'])->unique('gallery_album_id')->pluck('image', 'gallery_album_id');
        $page->getCollection()->each(fn ($album) => $album->cover_image ??= $covers[$album->id] ?? null);

        return $this->paginated($page, ['categories' => PublicCache::remember('facets:photo-categories', fn () => $this->galleryCategories('photo'))]);
    }

    public function album(string $slug): JsonResponse
    {
        $album = GalleryAlbum::query()->published()->where('slug', $slug)
            ->with(['category:id,name,slug', 'images' => fn ($q) => $q->published()->select(['id', 'gallery_album_id', 'title', 'image', 'description'])])
            ->firstOrFail(['id', 'gallery_category_id', 'title', 'slug', 'description', 'event_date']);

        return ApiResponse::success($album);
    }

    public function videos(Request $request): JsonResponse
    {
        $params = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $page = GalleryVideo::query()->published()->with('category:id,name,slug')
            ->when($params['category'] ?? null, fn ($q, $c) => $q->whereHas('category', fn ($w) => $w->where('slug', $c)))
            ->when($params['search'] ?? null, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderByDesc('is_featured')->ordered()
            ->paginate($params['per_page'] ?? 12, ['id', 'gallery_category_id', 'title', 'description', 'youtube_id', 'thumbnail', 'duration']);

        return $this->paginated($page, ['categories' => PublicCache::remember('facets:video-categories', fn () => $this->galleryCategories('video'))]);
    }

    private function galleryCategories(string $type): array
    {
        $relation = $type === 'photo' ? 'images' : 'videos';

        return GalleryCategory::query()->active()->ordered()->where('type', $type)
            ->withCount([$relation => fn ($q) => $q->published()])
            ->get(['id', 'name', 'slug', 'icon'])
            ->map(fn ($c) => ['value' => $c->slug, 'label' => $c->name, 'icon' => $c->icon, 'count' => $c->{"{$relation}_count"}])
            ->values()->all();
    }

    // ------------------------------------------------------------ blog & notices

    public function blogs(Request $request): JsonResponse
    {
        $params = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'destination' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:blog,notice'],
            'tag' => ['nullable', 'string', 'max:60'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:latest,oldest,popular'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $query = Blog::query()->published()
            ->with(['category:id,name,slug,type', 'destination:id,name,slug'])
            ->when($params['category'] ?? null, fn ($q, $c) => $q->whereHas('category', fn ($w) => $w->where('slug', $c)))
            ->when($params['destination'] ?? null, fn ($q, $d) => $q->whereHas('destination', fn ($w) => $w->where('slug', $d)))
            ->when($params['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($params['tag'] ?? null, fn ($q, $t) => $q->whereHas('tags', fn ($w) => $w->where('slug', $t)))
            ->when($params['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('excerpt', 'like', "%{$s}%")));

        match ($params['sort'] ?? 'latest') {
            'oldest' => $query->orderBy('published_at'),
            'popular' => $query->orderByDesc('views_count')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $page = $query->paginate($params['per_page'] ?? 9, self::POST_CARD);

        return $this->paginated($page, PublicCache::remember('blog:sidebar', fn () => $this->blogSidebar()));
    }

    public function blog(Request $request, string $slug): JsonResponse
    {
        $data = PublicCache::remember("blog:{$slug}", function () use ($slug) {
            $post = Blog::query()->published()->where('slug', $slug)
                ->with(['seo', 'category:id,name,slug,type', 'destination:id,name,slug', 'tags:id,name,slug', 'author:id,name'])
                ->first();

            if (! $post) {
                return null;
            }

            $words = str_word_count(strip_tags((string) $post->content));

            return $post->makeHidden(['status', 'deleted_at', 'created_at', 'author_id'])->toArray() + [
                'reading_minutes' => max(1, (int) ceil($words / 200)),
                'seo' => $post->seo?->only(['title', 'description', 'og_title', 'og_description', 'og_image', 'robots']),
                'related' => Blog::query()->published()->whereKeyNot($post->id)
                    ->orderByRaw('blog_category_id = ? desc', [$post->blog_category_id ?? 0])->orderByDesc('published_at')->limit(3)
                    ->with('category:id,name,slug')->get(self::POST_CARD),
            ];
        });

        abort_if($data === null, 404);

        // Count one view per visitor per post every 6 hours (outside the cached payload).
        if (Cache::add("blog-view:{$data['id']}:".sha1((string) $request->ip()), true, now()->addHours(6))) {
            Blog::query()->whereKey($data['id'])->increment('views_count');
        }

        return ApiResponse::success($data, meta: PublicCache::remember('blog:sidebar', fn () => $this->blogSidebar()));
    }

    /** Category tabs with counts + popular posts (shared by list and detail). */
    private function blogSidebar(): array
    {
        return [
            'categories' => BlogCategory::query()->active()->ordered()
                ->withCount(['blogs' => fn ($q) => $q->published()])->get(['id', 'name', 'slug', 'type', 'icon'])
                ->map(fn ($c) => ['value' => $c->slug, 'label' => $c->name, 'icon' => $c->icon, 'type' => $c->type, 'count' => $c->blogs_count])->values(),
            'all_count' => Blog::query()->published()->count(),
            'popular' => Blog::query()->published()->orderByDesc('views_count')->orderByDesc('published_at')->limit(4)
                ->get(['id', 'title', 'slug', 'featured_image', 'published_at']),
            'destinations' => Destination::query()->published()->whereHas('blogs', fn ($q) => $q->published())->ordered()
                ->get(['name', 'slug'])->map(fn ($d) => ['value' => $d->slug, 'label' => $d->name])->values(),
        ];
    }

    private function paginated(LengthAwarePaginator $page, array $extraMeta = []): JsonResponse
    {
        return ApiResponse::success($page->items(), meta: [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'from' => $page->firstItem(),
            'to' => $page->lastItem(),
        ] + $extraMeta);
    }
}
