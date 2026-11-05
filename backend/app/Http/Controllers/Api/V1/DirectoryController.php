<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StudyLevel;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Destination;
use App\Models\Service;
use App\Models\University;
use App\Services\ContentBlocks;
use App\Services\SectionPresenter;
use App\Support\ApiResponse;
use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Public destinations, universities, courses and services: lists with backend
 * search / filters / sorting / pagination, and detail pages with related content.
 */
class DirectoryController extends Controller
{
    public function __construct(private readonly ContentBlocks $blocks, private readonly SectionPresenter $sections) {}

    // ------------------------------------------------------------ destinations

    public function destinations(Request $request): JsonResponse
    {
        $params = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:48']]);

        $page = Destination::query()->published()
            ->when($params['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('tagline', 'like', "%{$s}%")))
            ->withCount(['universities' => fn ($q) => $q->published(), 'courses' => fn ($q) => $q->published()])
            ->ordered()
            ->paginate($params['per_page'] ?? 12, ['id', 'name', 'slug', 'country_code', 'thumbnail', 'hero_image', 'tagline', 'short_description']);

        return $this->paginated($page);
    }

    public function destination(string $slug): JsonResponse
    {
        $data = PublicCache::remember("destination:{$slug}", function () use ($slug) {
            $destination = Destination::query()->published()->where('slug', $slug)
                ->with(['seo', 'activeSections'])
                ->withCount(['universities' => fn ($q) => $q->published(), 'courses' => fn ($q) => $q->published()])
                ->first();

            if (! $destination) {
                return null;
            }

            return $destination->makeHidden(['active_sections', 'sort_order', 'status', 'deleted_at', 'created_at'])->toArray() + [
                'seo' => $destination->seo?->only(['title', 'description', 'og_title', 'og_description', 'og_image', 'robots']),
                'sections' => $this->sections->presentAll($destination->activeSections, $destination),
                'related' => Destination::query()->published()->whereKeyNot($destination->id)->orderByDesc('is_featured')->ordered()->limit(4)
                    ->get(['id', 'name', 'slug', 'country_code', 'thumbnail', 'tagline'])->toArray(),
            ];
        });

        abort_if($data === null, 404);

        return ApiResponse::success($data);
    }

    // ------------------------------------------------------------ universities

    public function universities(Request $request): JsonResponse
    {
        $params = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable', 'array'], 'destination.*' => ['string', 'max:100'],
            'type' => ['nullable', 'array'], 'type.*' => ['in:public,private'],
            'subject' => ['nullable', 'array'], 'subject.*' => ['string', 'max:120'],
            'level' => ['nullable', 'array'], 'level.*' => ['string', 'max:30'],
            'fee_max' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:featured,ranking,name,fee_low,fee_high'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $query = University::query()->published()
            ->with('destination:id,name,slug,country_code')
            ->withCount(['courses' => fn ($q) => $q->published()])
            ->when($params['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('city', 'like', "%{$s}%")))
            ->when($params['destination'] ?? null, fn ($q, $d) => $q->whereHas('destination', fn ($w) => $w->whereIn('slug', $d)))
            ->when($params['type'] ?? null, fn ($q, $t) => $q->whereIn('type', $t))
            ->when($params['subject'] ?? null, fn ($q, $s) => $q->whereHas('courses', fn ($w) => $w->published()->whereIn('subject', $s)))
            ->when($params['level'] ?? null, fn ($q, $l) => $q->whereHas('courses', fn ($w) => $w->published()->whereIn('study_level', $l)))
            ->when(isset($params['fee_max']), fn ($q) => $q->where(fn ($w) => $w->whereNull('tuition_fee_min')->orWhere('tuition_fee_min', '<=', $params['fee_max'])));

        $this->sortUniversities($query, $params['sort'] ?? 'featured');

        $page = $query->paginate($params['per_page'] ?? 12, ContentBlocks::UNIVERSITY_CARD);

        return $this->paginated($page, ['facets' => PublicCache::remember('facets:universities', fn () => $this->universityFacets())]);
    }

    public function university(string $slug): JsonResponse
    {
        $data = PublicCache::remember("university:{$slug}", function () use ($slug) {
            $university = University::query()->published()->where('slug', $slug)
                ->with(['seo', 'activeSections', 'destination:id,name,slug,country_code'])
                ->withCount(['courses' => fn ($q) => $q->published()])
                ->first();

            if (! $university) {
                return null;
            }

            return $university->makeHidden(['active_sections', 'sort_order', 'status', 'deleted_at', 'created_at'])->toArray() + [
                'seo' => $university->seo?->only(['title', 'description', 'og_title', 'og_description', 'og_image', 'robots']),
                'sections' => $this->sections->presentAll($university->activeSections, $university),
                'related' => $this->blocks->universityCards(fn ($q) => $q->where('destination_id', $university->destination_id)->whereKeyNot($university->id), 4),
            ];
        });

        abort_if($data === null, 404);

        return ApiResponse::success($data);
    }

    // ------------------------------------------------------------ courses

    public function courses(Request $request): JsonResponse
    {
        $params = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable', 'array'], 'destination.*' => ['string', 'max:100'],
            'university' => ['nullable', 'array'], 'university.*' => ['string', 'max:190'],
            'level' => ['nullable', 'array'], 'level.*' => ['string', 'max:30'],
            'subject' => ['nullable', 'array'], 'subject.*' => ['string', 'max:120'],
            'degree' => ['nullable', 'array'], 'degree.*' => ['string', 'max:50'],
            'fee_max' => ['nullable', 'numeric', 'min:0'],
            'duration_max' => ['nullable', 'integer', 'min:1', 'max:120'],
            'sort' => ['nullable', 'in:featured,name,fee_low,fee_high,duration'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $query = Course::query()->published()
            ->whereHas('university', fn ($q) => $q->published())
            ->with(['university:id,name,slug,logo', 'destination:id,name,slug,country_code'])
            ->when($params['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('courses.name', 'like', "%{$s}%")->orWhere('subject', 'like', "%{$s}%")->orWhere('degree_type', 'like', "%{$s}%")))
            ->when($params['destination'] ?? null, fn ($q, $d) => $q->whereHas('destination', fn ($w) => $w->whereIn('slug', $d)))
            ->when($params['university'] ?? null, fn ($q, $u) => $q->whereHas('university', fn ($w) => $w->whereIn('slug', $u)))
            ->when($params['level'] ?? null, fn ($q, $l) => $q->whereIn('study_level', $l))
            ->when($params['subject'] ?? null, fn ($q, $s) => $q->whereIn('subject', $s))
            ->when($params['degree'] ?? null, fn ($q, $d) => $q->whereIn('degree_type', $d))
            ->when(isset($params['fee_max']), fn ($q) => $q->where(fn ($w) => $w->whereNull('tuition_fee')->orWhere('tuition_fee', '<=', $params['fee_max'])))
            ->when(isset($params['duration_max']), fn ($q) => $q->where('duration_months', '<=', $params['duration_max']));

        match ($params['sort'] ?? 'featured') {
            'name' => $query->orderBy('courses.name'),
            'fee_low' => $query->orderByRaw('courses.tuition_fee IS NULL')->orderBy('courses.tuition_fee'),
            'fee_high' => $query->orderByDesc('courses.tuition_fee'),
            'duration' => $query->orderByRaw('courses.duration_months IS NULL')->orderBy('courses.duration_months'),
            default => $query->orderByDesc('courses.is_featured')->ordered(),
        };

        $page = $query->paginate($params['per_page'] ?? 12, ContentBlocks::COURSE_CARD);

        return $this->paginated($page, ['facets' => PublicCache::remember('facets:courses', fn () => $this->courseFacets())]);
    }

    public function course(string $slug): JsonResponse
    {
        $data = PublicCache::remember("course:{$slug}", function () use ($slug) {
            $course = Course::query()->published()->where('slug', $slug)
                ->whereHas('university', fn ($q) => $q->published())
                ->with(['seo', 'university:id,name,slug,logo,cover_image,thumbnail,city,type,world_ranking,website,short_description', 'destination:id,name,slug,country_code'])
                ->first();

            if (! $course) {
                return null;
            }

            return $course->makeHidden(['sort_order', 'status', 'deleted_at', 'created_at'])->toArray() + [
                'seo' => $course->seo?->only(['title', 'description', 'og_title', 'og_description', 'og_image', 'robots']),
                'related' => $this->blocks->courseCards(fn ($q) => $q->whereKeyNot($course->id)->where(fn ($w) => $w
                    ->where('university_id', $course->university_id)->orWhere(fn ($x) => $x->whereNotNull('subject')->where('subject', $course->subject))), 4),
            ];
        });

        abort_if($data === null, 404);

        return ApiResponse::success($data);
    }

    // ------------------------------------------------------------ services

    public function services(): JsonResponse
    {
        return ApiResponse::success(PublicCache::remember('services', fn () => $this->blocks->services(50, featuredOnly: false)));
    }

    public function service(string $slug): JsonResponse
    {
        $data = PublicCache::remember("service:{$slug}", function () use ($slug) {
            $service = Service::query()->published()->where('slug', $slug)->with(['seo', 'activeSections'])->first();
            if (! $service) {
                return null;
            }

            return $service->makeHidden(['active_sections', 'sort_order', 'status', 'deleted_at', 'created_at'])->toArray() + [
                'seo' => $service->seo?->only(['title', 'description', 'og_title', 'og_description', 'og_image', 'robots']),
                'sections' => $this->sections->presentAll($service->activeSections, $service),
                'related' => Service::query()->published()->whereKeyNot($service->id)->ordered()->limit(4)
                    ->get(['id', 'title', 'slug', 'icon', 'image', 'short_description'])->toArray(),
            ];
        });

        abort_if($data === null, 404);

        return ApiResponse::success($data);
    }

    // ------------------------------------------------------------ helpers

    private function sortUniversities(Builder $query, string $sort): void
    {
        match ($sort) {
            'ranking' => $query->orderByRaw('world_ranking IS NULL')->orderBy('world_ranking'),
            'name' => $query->orderBy('name'),
            'fee_low' => $query->orderByRaw('tuition_fee_min IS NULL')->orderBy('tuition_fee_min'),
            'fee_high' => $query->orderByDesc('tuition_fee_max'),
            default => $query->orderByDesc('is_featured')->ordered(),
        };
    }

    /** Option lists with counts for the university filter sidebar (over all published universities). */
    private function universityFacets(): array
    {
        $published = University::query()->published();

        return [
            'destinations' => Destination::query()->published()->ordered()
                ->withCount(['universities' => fn ($q) => $q->published()])->get(['id', 'name', 'slug', 'country_code'])
                ->filter(fn ($d) => $d->universities_count > 0)
                ->map(fn ($d) => ['value' => $d->slug, 'label' => $d->name, 'country_code' => $d->country_code, 'count' => $d->universities_count])->values(),
            'types' => (clone $published)->selectRaw('type as value, count(*) as count')->groupBy('type')->get()
                ->map(fn ($r) => ['value' => $r->value, 'label' => ucfirst($r->value).' University', 'count' => (int) $r->count])->values(),
            'subjects' => $this->courseGroupCounts('subject', 'university_id'),
            'levels' => $this->levelOptions($this->courseGroupCounts('study_level', 'university_id')),
            'fee_range' => ['min' => 0, 'max' => (float) ((clone $published)->max('tuition_fee_max') ?: (clone $published)->max('tuition_fee_min') ?: 0)],
        ];
    }

    private function courseFacets(): array
    {
        $courses = Course::query()->published();

        return [
            'destinations' => Destination::query()->published()->ordered()
                ->withCount(['courses' => fn ($q) => $q->published()])->get(['id', 'name', 'slug', 'country_code'])
                ->filter(fn ($d) => $d->courses_count > 0)
                ->map(fn ($d) => ['value' => $d->slug, 'label' => $d->name, 'country_code' => $d->country_code, 'count' => $d->courses_count])->values(),
            'levels' => $this->levelOptions($this->courseGroupCounts('study_level', 'id')),
            'subjects' => $this->courseGroupCounts('subject', 'id'),
            'degrees' => $this->courseGroupCounts('degree_type', 'id'),
            'fee_range' => ['min' => 0, 'max' => (float) ((clone $courses)->max('tuition_fee') ?: 0)],
        ];
    }

    /** Count distinct $countColumn (e.g. universities) per course $groupColumn value. */
    private function courseGroupCounts(string $groupColumn, string $countColumn): array
    {
        return Course::query()->published()->whereNotNull($groupColumn)
            ->selectRaw("{$groupColumn} as value, count(distinct {$countColumn}) as count")
            ->groupBy($groupColumn)->orderBy($groupColumn)->get()
            ->map(fn ($r) => ['value' => $r->value, 'label' => $r->value, 'count' => (int) $r->count])->all();
    }

    private function levelOptions(array $counts): array
    {
        return collect($counts)->map(fn ($r) => ['label' => StudyLevel::tryFrom($r['value'])?->label() ?? $r['value']] + $r)->all();
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
