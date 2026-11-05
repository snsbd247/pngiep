<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Course;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Service;
use App\Models\SuccessStory;
use App\Models\University;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/search?q=…: grouped site-wide results.
 */
class SearchController extends Controller
{
    private const PER_GROUP = 6;

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q']);
        $like = '%'.addcslashes($q, '%_\\').'%';

        $groups = [
            'destinations' => Destination::query()->published()
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('tagline', 'like', $like)->orWhere('short_description', 'like', $like))
                ->ordered()->limit(self::PER_GROUP)->get(['id', 'name', 'slug', 'country_code', 'thumbnail', 'tagline'])
                ->map(fn ($d) => ['id' => $d->id, 'title' => $d->name, 'text' => $d->tagline, 'image' => $d->thumbnail, 'url' => "/study-in-{$d->slug}", 'country_code' => $d->country_code]),
            'universities' => University::query()->published()->with('destination:id,name,country_code')
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('city', 'like', $like)->orWhere('short_description', 'like', $like))
                ->ordered()->limit(self::PER_GROUP)->get(['id', 'destination_id', 'name', 'slug', 'logo', 'thumbnail', 'city'])
                ->map(fn ($u) => ['id' => $u->id, 'title' => $u->name, 'text' => trim(($u->city ? "{$u->city}, " : '').($u->destination?->name ?? ''), ', '), 'image' => $u->logo ?: $u->thumbnail, 'url' => "/universities/{$u->slug}", 'country_code' => $u->destination?->country_code]),
            'courses' => Course::query()->published()->with('university:id,name')
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('subject', 'like', $like)->orWhere('degree_type', 'like', $like))
                ->ordered()->limit(self::PER_GROUP)->get(['id', 'university_id', 'name', 'slug', 'image', 'study_level'])
                ->map(fn ($c) => ['id' => $c->id, 'title' => $c->name, 'text' => $c->university?->name, 'image' => $c->image, 'url' => "/courses/{$c->slug}"]),
            'services' => Service::query()->published()
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('short_description', 'like', $like))
                ->ordered()->limit(self::PER_GROUP)->get(['id', 'title', 'slug', 'image', 'short_description'])
                ->map(fn ($s) => ['id' => $s->id, 'title' => $s->title, 'text' => $s->short_description, 'image' => $s->image, 'url' => "/services/{$s->slug}"]),
            'success_stories' => SuccessStory::query()->published()->with('destination:id,country_code')
                ->where(fn ($w) => $w->where('student_name', 'like', $like)->orWhere('university_name', 'like', $like)->orWhere('course_name', 'like', $like))
                ->ordered()->limit(self::PER_GROUP)->get(['id', 'student_name', 'slug', 'photo', 'destination_id', 'university_name', 'course_name'])
                ->map(fn ($s) => ['id' => $s->id, 'title' => $s->student_name, 'text' => trim("{$s->course_name}, {$s->university_name}", ', '), 'image' => $s->photo, 'url' => "/success-stories/{$s->slug}", 'country_code' => $s->destination?->country_code]),
            'blog' => Blog::query()->published()
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like))
                ->latest('published_at')->limit(self::PER_GROUP)->get(['id', 'title', 'slug', 'featured_image', 'excerpt'])
                ->map(fn ($b) => ['id' => $b->id, 'title' => $b->title, 'text' => $b->excerpt, 'image' => $b->featured_image, 'url' => "/blog/{$b->slug}"]),
            'pages' => Page::query()->published()->where('template', '!=', 'listing')
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('subtitle', 'like', $like)->orWhere('excerpt', 'like', $like))
                ->limit(self::PER_GROUP)->get(['id', 'title', 'slug', 'subtitle', 'banner_image'])
                ->map(fn ($p) => ['id' => $p->id, 'title' => str_replace('*', '', $p->title), 'text' => $p->subtitle, 'image' => $p->banner_image, 'url' => "/{$p->slug}"]),
        ];

        $groups = array_filter(array_map(fn ($c) => $c->values()->all(), $groups));

        return ApiResponse::success($groups, meta: ['query' => $q, 'total' => array_sum(array_map('count', $groups))]);
    }
}
